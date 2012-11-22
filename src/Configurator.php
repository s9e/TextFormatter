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
			return $this->plugins->get($k);
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
		$bitfields = RulesHelper::getBitfield($this->tags);

		// Save the root context
		$config['rootContext'] = $bitfields['rootContext'];

		// Remove unused tags
		$config['tags'] = array_intersect_key($config['tags'], $bitfields['tags']);

		// Add the bitfield information to each tag
		foreach ($bitfields['tags'] as $tagName => $tagBitfields)
		{
			$config['tags'][$tagName] += $tagBitfields;
		}

		// Remove unused plugins
		$config['plugins'] = array_filter($config['plugins']);

		return $config;
	}

	/**
	* Test the filterChain of every tag/attribute and generate a warning for every invalid filter
	*
	* @return void
	*/
	protected function warnAboutUnknownFilters()
	{
		foreach ($this->tags as $tagName => $tag)
		{
			foreach ($tag->filterChain as $k => $filter)
			{
				if (!$this->filterIsValid($filter))
				{
					trigger_error('Filter #' . $k . " of tag '" . $tagName . "' is invalid", E_USER_WARNING);
				}
			}

			foreach ($tag->attributes as $attrName => $attribute)
			{
				foreach ($attribute->filterChain as $k => $filter)
				{
					if (!$this->filterIsValid($filter))
					{
						trigger_error('Filter #' . $k . " used in attribute '" . $attrName . "' of tag '" . $tagName . "' is invalid", E_USER_WARNING);
					}
				}
			}
		}
	}

	/**
	* Test whether a filter is valid
	*
	* Currently only tests whether the callback exists, but could be expanded to test other
	* conditions as well, such as whether the values in a #range are valid.
	*
	* @param  Filter $filter
	* @return bool
	*/
	protected function filterIsValid(Filter $filter)
	{
		$callback = $filter->getCallback();

		// Test whether this callback is anything but a built-in/custom filter
		if (!is_string($callback) || $callback[0] !== '#')
		{
			return true;
		}

		// Remove the # sign from the start of the name
		$filterName = substr($callback, 1);

		// Test whether it's the tag's default processing filter
		if ($filterName === 'default')
		{
			return true;
		}

		// Test whether we have a custom filter by that name
		if (isset($this->customFilters[$filterName]))
		{
			return true;
		}

		// Test whether we have a built-in filter by that name
		$className = 's9e\\TextFormatter\\Parser\\Filters\\' . ucfirst($filterName);
		if (class_exists($className))
		{
			return true;
		}

		return false;
	}

	// NOTE: when building the JS config, keys from Collections should probably automatically be preserved, although not always (e.g. rule names?)
















	//==========================================================================
	// Plugins
	//==========================================================================

	//==========================================================================
	// Factories
	//==========================================================================

	/**
	* Return the configs generated by plugins
	*
	* @param  string $method Either "getConfig" or "getJSConfig"
	* @return array
	*/
	public function getPluginsConfig($method = 'getConfig')
	{
		$config = array();

		foreach ($this->getLoadedPlugins() as $pluginName => $plugin)
		{
			$pluginConfig = $plugin->$method();

			if ($pluginConfig === false)
			{
				/**
				* This plugin is disabled
				*/
				continue;
			}

			/**
			* Add some default config if missing
			*/
			if (isset($pluginConfig['regexp']))
			{
				foreach (array('regexpLimit', 'regexpLimitAction') as $k)
				{
					if (!isset($pluginConfig[$k]))
					{
						$pluginConfig[$k] = $plugin->$k;
					}
				}
			}

			$config[$pluginName] = $pluginConfig;
		}

		return $config;
	}

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
		return TemplateHelper::getXSL($this);
	}
}