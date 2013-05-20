<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
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
use s9e\TextFormatter\Configurator\RendererGenerators\XSLT;
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
		$this->attributeFilters  = new AttributeFilterCollection;
		$this->javascript        = new JavaScript($this);
		$this->plugins           = new PluginCollection($this);
		$this->rendererGenerator = new XSLT;
		$this->rootRules         = new Ruleset;
		$this->tags              = new TagCollection;
		$this->templateChecker   = new TemplateChecker;
		$this->stylesheet        = new Stylesheet($this);
		$this->urlConfig         = new UrlConfig;
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
	* @return Renderer
	*/
	public function getRenderer()
	{
		return $this->rendererGenerator->getRenderer($this->stylesheet);
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

		// Create the config array
		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);

		// Save the root context
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];

		// Make sure those keys exist even if no tags were defined and plugins loaded
		$config += ['plugins' => [], 'tags' => []];

		// Remove unused tags
		$config['tags'] = array_intersect_key($config['tags'], $bitfields['tags']);

		// Add the bitfield information to each tag
		foreach ($bitfields['tags'] as $tagName => $tagBitfields)
		{
			$config['tags'][$tagName] += $tagBitfields;
		}

		// Move the URL config to the registered vars, for use in filters
		$config['registeredVars']['urlConfig'] = $config['urlConfig'];
		unset($config['urlConfig']);

		// Remove unused entries
		unset($config['rootRules']);

		// Create a variant that adds the stylesheet to the config if we're building a JavaScript
		// config
		$config['stylesheet'] = new Variant;
		$config['stylesheet']->setDynamic('JS', [$this->stylesheet, 'get']);

		return $config;
	}
}