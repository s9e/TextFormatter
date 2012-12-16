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
use s9e\TextFormatter\Configurator\Helpers\RulesHelper;
use s9e\TextFormatter\Configurator\Helpers\StylesheetHelper;
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
	* Generate and return the complete config array
	*
	* @return array
	*/
	public function asConfig()
	{
		$config    = ConfigHelper::toArray($this);
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

		// Remove unused plugins
		$config['plugins'] = array_filter($config['plugins']);

		// Adjust plugins' default properties
		foreach ($config['plugins'] as $pluginName => &$pluginConfig)
		{
			// Add base properties
			$pluginConfig += $this->plugins[$pluginName]->getBaseProperties();

			// Remove quickMatch if it's false
			if ($pluginConfig['quickMatch'] === false)
			{
				unset($pluginConfig['quickMatch']);
			}
		}
		unset($pluginConfig);

		// Remove unused properties
		unset($config['customFilters']);
		unset($config['rootRules']);

		return $config;
	}

	// NOTE: when building the JS config, keys from Collections should probably automatically be preserved, although not always (e.g. rule names?)





	//==========================================================================
	// XSL stuff
	//==========================================================================

	/**
	* Return the XSL used for rendering
	*
	* @param  string $prefix Prefix to use for XSL elements (defaults to "xsl")
	* @return string
	*/
	public function getXSL($prefix = 'xsl')
	{
		return StylesheetHelper::generate($this->tags);
	}
}