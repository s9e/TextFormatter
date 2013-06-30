<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use ReflectionClass;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\AttributeFilterCollection;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\HTML5\RulesGenerator;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Configurator\TemplateChecker;
use s9e\TextFormatter\Configurator\UrlConfig;

class Configurator implements ConfigProvider
{
	/**
	* @var AttributeFilterCollection Dynamically-populated collection of AttributeFilter instances
	*/
	public $attributeFilters;

	/**
	* @var JavaScript JavaScript manipulation object
	*/
	public $javascript;

	/**
	* @var PluginCollection Loaded plugins
	*/
	public $plugins;

	/**
	* @var array Array of variables that are available to the filters during parsing
	*/
	public $registeredVars;

	/**
	* @var RendererGenerator Generator used by $this->getRenderer()
	*/
	public $rendererGenerator;

	/**
	* @var Ruleset Rules that apply at the root of the text
	*/
	public $rootRules;

	/**
	* @var Stylesheet Stylesheet object
	*/
	public $stylesheet;

	/**
	* @var TagCollection Tags repository
	*/
	public $tags;

	/**
	* @var TemplateChecker Default template checker
	*/
	public $templateChecker;

	/**
	* @var UrlConfig Config options related to URL validation
	*/
	public $urlConfig;

	/**
	* Constructor
	*
	* Prepares the collections that hold tags and filters, the UrlConfig object as well as the
	* various helpers required to generate a full config.
	*/
	public function __construct()
	{
		$this->attributeFilters = new AttributeFilterCollection;
		$this->javascript       = new JavaScript($this);
		$this->plugins          = new PluginCollection($this);
		$this->rootRules        = new Ruleset;
		$this->tags             = new TagCollection;
		$this->templateChecker  = new TemplateChecker;
		$this->stylesheet       = new Stylesheet($this);
		$this->urlConfig        = new UrlConfig;
		$this->registeredVars   = ['urlConfig' => $this->urlConfig];

		$this->setRendererGenerator('XSLT');
	}

	/**
	* Magic __get automatically loads plugins, PredefinedTags class
	*
	* @param  string $k Property name
	* @return mixed
	*/
	public function __get($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			return ($this->plugins->exists($k))
			      ? $this->plugins->get($k)
			      : $this->plugins->load($k);
		}

		throw new RuntimeException("Undefined property '" . __CLASS__ . '::$' . $k . "'");
	}

	/**
	* Magic __isset forwards to the plugins collection when applicable
	*
	* @param  string $k Property name
	* @return bool
	*/
	public function __isset($k)
	{
		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			return $this->plugins->exists($k);
		}

		return isset($this->$k);
	}

	/**
	* Return an instance of Parser based on the current config
	*
	* @return Parser
	*/
	public function getParser()
	{
		// Generate the config array
		$config = $this->asConfig();

		// Remove variants
		ConfigHelper::filterVariants($config);

		return new Parser($config);
	}

	/**
	* Return an instance of Renderer based on the current config
	*
	* NOTE: extra parameters are passed to the RendererGenerator's constructor
	*
	* @param  string $name Name of the RendererGenerator, e.g. "PHP"
	* @return Renderer
	*/
	public function getRenderer($name = null)
	{
		if (isset($name))
		{
			// Create a specific generator
			$rendererGenerator = $this->getRendererGenerator(func_get_args());
		}
		else
		{
			// Use the default renderer
			$rendererGenerator = $this->rendererGenerator;
		}

		return $rendererGenerator->getRenderer($this->stylesheet);
	}

	/**
	* Add the rules that are generated based on HTML5 specs
	*
	* @see s9e\TextFormatter\ConfigBuilder\Helpers\HTML5\RulesGenerator
	*
	* @param  array $options Options passed to RulesGenerator::getRules()
	* @return void
	*/
	public function addHTML5Rules(array $options = [])
	{
		// Add the default options
		$options += [
			'renderer'   => $this->getRenderer(),
			'stylesheet' => $this->stylesheet
		];

		// Get the rules
		$rules = RulesGenerator::getRules($this->tags, $options);

		// Add the rules pertaining to the root
		$this->rootRules->merge($rules['root']);

		// Add the rules pertaining to each tag
		foreach ($rules['tags'] as $tagName => $tagRules)
		{
			$this->tags[$tagName]->rules->merge($tagRules);
		}
	}

	/**
	* Generate and return the complete config array
	*
	* @return array
	*/
	public function asConfig()
	{
		$properties = get_object_vars($this);

		// Remove properties that shouldn't be turned into config arrays
		unset($properties['attributeFilters']);
		unset($properties['javascript']);
		unset($properties['rendererGenerator']);
		unset($properties['templateChecker']);
		unset($properties['stylesheet']);
		unset($properties['urlConfig']);

		// Create the config array
		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);

		// Save the root context
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];

		// Make sure those keys exist even if they're empty
		$config += [
			'plugins'        => [],
			'registeredVars' => [],
			'tags'           => []
		];

		// Remove unused tags
		$config['tags'] = array_intersect_key($config['tags'], $bitfields['tags']);

		// Add the bitfield information to each tag
		foreach ($bitfields['tags'] as $tagName => $tagBitfields)
		{
			$config['tags'][$tagName] += $tagBitfields;
		}

		// Remove unused entries
		unset($config['rootRules']);

		// Create a variant that adds the stylesheet to the config if we're building a JavaScript
		// config
		$config['stylesheet'] = new Variant;
		$config['stylesheet']->setDynamic('JS', [$this->stylesheet, 'get']);

		return $config;
	}

	/**
	* Set the RendererGenerator instance used by this Configurator
	*
	* NOTE: extra parameters are passed to the RendererGenerator's constructor
	*
	* @param  string $name Name of the RendererGenerator, e.g. "PHP"
	* @return void
	*/
	public function setRendererGenerator($name)
	{
		$this->rendererGenerator = $this->getRendererGenerator(func_get_args());
	}

	/**
	* Generate and return an instance of RendererGenerator
	*
	* @param  array $args List of arguments, starting with the name of the generator
	* @return RendererGenerator
	*/
	protected function getRendererGenerator(array $args)
	{
		$className  = 's9e\\TextFormatter\\Configurator\\RendererGenerators\\' . $args[0];
		$reflection = new ReflectionClass($className);

		return $reflection->newInstanceArgs(array_slice($args, 1));
	}
}