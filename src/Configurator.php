<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Collections\Collection;
use s9e\TextFormatter\Configurator\Collections\FilterCollection;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\Ruleset;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
use s9e\TextFormatter\Configurator\Helpers\HTML5\RulesGenerator;
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\Stylesheet;
use s9e\TextFormatter\Configurator\UrlConfig;

class Configurator implements ConfigProvider
{
	/**
	* @var FilterCollection Custom filters
	*/
	public $customFilters;

	/**
	* @var PluginCollection Loaded plugins
	*/
	public $plugins;

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
		$this->customFilters = new FilterCollection;
		$this->plugins       = new PluginCollection($this);
		$this->rootRules     = new Ruleset;
		$this->tags          = new TagCollection;
		$this->stylesheet    = new Stylesheet($this->tags);
		$this->urlConfig     = new UrlConfig;
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
		return new Parser($this->asConfig());
	}

	/**
	* Return an instance of Renderer based on the current config
	*
	* @return Renderer
	*/
	public function getRenderer()
	{
		return new Renderer($this->getXSL());
	}

	/**
	* Add the rules that are generated based on HTML5 specs
	*
	* @see s9e\TextFormatter\ConfigBuilder\Helpers\HTML5\RulesGenerator
	*
	* @param  array $options Options passed to RulesGenerator::getRules()
	* @return void
	*/
	public function addHTML5Rules(array $options = array())
	{
		// Add the default options
		$options += array(
			'renderer'   => $this->getRenderer(),
			'stylesheet' => $this->stylesheet
		);

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
		unset($properties['stylesheet']);

		$config    = ConfigHelper::toArray($properties);
		$bitfields = RulesHelper::getBitfields($this->tags, $this->rootRules);

		// Save the root context
		$config['rootContext'] = $bitfields['root'];
		$config['rootContext']['flags'] = $config['rootRules']['flags'];

		// Make sure those keys exist even if no tags were defined and plugins loaded
		$config += array('plugins' => array(), 'tags' => array());

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

		// Replace built-in and custom filters
		ConfigHelper::replaceBuiltInFilters($config['tags'], $this->customFilters);

		// Remove unused properties
		unset($config['customFilters']);
		unset($config['rootRules']);

		return $config;
	}

	/**
	* Return the XSL used for rendering
	*
	* @return string
	*/
	public function getXSL()
	{
		return $this->stylesheet->get();
	}
}