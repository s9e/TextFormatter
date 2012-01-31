<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException,
    RuntimeException,
    s9e\TextFormatter\ConfigBuilder\Collection,
    s9e\TextFormatter\ConfigBuilder\PredefinedTags,
    s9e\TextFormatter\ConfigBuilder\UrlConfig;

class ConfigBuilder
{
	/**
	* @var Collection Custom filters
	*/
	public $customFilters;

	/**
	* @var Collection Tags repository
	*/
	public $tags;

	/**
	* @var UrlConfig Config options related to URL validation
	*/
	public $urlConfig;

	public function __construct()
	{
		$this->tags          = new Collection(__CLASS__ . '\\Tag');
		$this->customFilters = new Collection(__CLASS__ . '\\Filter');
		$this->urlConfig     = new UrlConfig;
	}

	//==========================================================================
	// Plugins
	//==========================================================================

	/**
	* Get all loaded plugins
	*
	* @return array
	*/
	public function getLoadedPlugins()
	{
		$plugins = array();

		foreach (get_object_vars($this) as $k => $v)
		{
			if ($v instanceof PluginConfig)
			{
				$plugins[$k] = $v;
			}
		}

		return $plugins;
	}

	/**
	* Magic __get automatically loads plugins, PredefinedTags class
	*
	* @param  string $k Property name
	* @return mixed
	*/
	public function __get($k)
	{
		if ($k === 'predefinedTags')
		{
			return $this->predefinedTags = new PredefinedTags($this);
		}

		if (preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $k))
		{
			return $this->loadPlugin($k);
		}

		throw new RuntimeException("Undefined property '" . __CLASS__ . '::$' . $k . "'");
	}

	/**
	* Load a plugin
	*
	* If a plugin of the same name exists, it will be overwritten.
	*
	* @param  string $pluginName    Name of the plugin
	* @param  string $className     Name of the plugin's config class (required for custom plugins)
	* @param  array  $overrideProps Properties of the plugin will be overwritten with those
	* @return PluginConfig
	*/
	public function loadPlugin($pluginName, $className = null, array $overrideProps = array())
	{
		if (!preg_match('#^[A-Z][A-Za-z_0-9]+$#D', $pluginName))
		{
			throw new InvalidArgumentException("Invalid plugin name '" . $pluginName . "'");
		}

		if (!isset($className))
		{
			$className = __NAMESPACE__ . '\\Plugins\\' . $pluginName . 'Config';
		}

		// If a filepath was provided, load the plugin's file if its class does not exist
		if (isset($classFilepath)
		 && !class_exists($className, false)
		 && file_exists($classFilepath))
		{
			include $classFilepath;
		}

		if (!class_exists($className))
		{
			throw new RuntimeException("Class '" . $className . "' does not exist");
		}

		return $this->$pluginName = new $className($this, $overrideProps);
	}

	//==========================================================================
	// Factories
	//==========================================================================

	/**
	* Return an instance of Parser based on the current config
	*
	* @return Parser
	*/
	public function getParser()
	{
		return new Parser($this->getParserConfig());
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
	* Return a (cached) instance of RegexpMaster
	*
	* @return RegexpMaster
	*/
	public function getRegexpMaster()
	{
		static $rm;

		if (!isset($rm))
		{
			$rm = new RegexpMaster;
		}

		return $rm;
	}

	//==========================================================================
	// Config
	//==========================================================================

	/**
	* Return the config needed by the global parser
	*
	* @param  bool  $keepJs Whether to keep the Javascript filters in the array
	* @return array
	*/
	public function getParserConfig($keepJs = false)
	{
		$config = array(
			'urlConfig' => $this->getUrlConfig(),
			'plugins'   => $this->getPluginsConfig(),
			'tags'      => $this->getTagsConfig(true)
		);

		foreach ($this->filters as $filterName => $filter)
		{
			$filterConf = $filter->toArray();

			if (!$keepJs)
			{
				unset($filterConf['js']);
			}

			$config['filters'][$filterName] = $filterConf;
		}

		if (!empty($this->namespaces))
		{
			foreach ($this->tags as $tagName => $tagConfig)
			{
				$pos = strpos($tagName, ':');

				if ($pos)
				{
					$prefix = substr($tagName, 0, $pos);
					$config['namespaces'][$prefix] = $this->namespaces[$prefix];
				}
			}
		}

		/**
		* Generate the root context to be used by the Parser
		*/
		$config['rootContext'] = array(
			'allowedChildren'    => str_repeat("\x00", ceil(count($config['tags']) / 8)),
			'allowedDescendants' => str_repeat("\x00", ceil(count($config['tags']) / 8))
		);

		foreach ($config['tags'] as &$tagConfig)
		{
			$n = $tagConfig['n'];

			// We set the bit only if the tag is allowed at the root of document
			if (empty($tagConfig['disallowAsRoot']))
			{
				$config['rootContext']['allowedChildren'][$n >> 3]
					= $config['rootContext']['allowedChildren'][$n >> 3] | chr(1 << ($n & 7));
			}

			$config['rootContext']['allowedDescendants'][$n >> 3]
				= $config['rootContext']['allowedDescendants'][$n >> 3] | chr(1 << ($n & 7));

			// We don't need this anymore
			unset($tagConfig['disallowAsRoot']);
		}
		unset($tagConfig);

		return $config;
	}

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

	/**
	* Return the tags' config, normalized and sorted, minus the tags' templates
	*
	* @param  bool  $reduce If true, remove unnecessary/empty entries and build the list of allowed
	*                       decendants for each tag
	* @return array
	*/
	public function getTagsConfig($reduce = false)
	{
		$tagsConfig = $this->tags;
		ksort($tagsConfig);

		$n = -1;

		foreach ($tagsConfig as $tagName => &$tagConfig)
		{
			if ($reduce)
			{
				if ($tagConfig['disable'])
				{
					// This tag is disabled, remove it
					unset($tagsConfig[$tagName]);
					continue;
				}

				$tagConfig['n'] = ++$n;

				/**
				* Build the list of allowed children and descendants.
				* Note: $tagsConfig is already sorted, so we don't have to sort the list
				*/
				$tagConfig['allowedChildren'] = array_fill_keys(
					array_keys($tagsConfig),
					($tagConfig['defaultChildRule'] === 'allow') ? '1' : '0'
				);
				$tagConfig['allowedDescendants'] = array_fill_keys(
					array_keys($tagsConfig),
					($tagConfig['defaultDescendantRule'] === 'allow') ? '1' : '0'
				);

				if (isset($tagConfig['rules']))
				{
					/**
					* Sort the rules so that "deny" overwrites "allow"
					*/
					ksort($tagConfig['rules']);

					foreach ($tagConfig['rules'] as $action => &$targets)
					{
						switch ($action)
						{
							case 'allowChild':
							case 'allowDescendant':
							case 'denyChild':
							case 'denyDescendant':
								/**
								* Those rules are converted into the allowedChildren and
								* allowedDescendants bitmaps
								*/
								$k = (substr($action, -5) === 'Child')
								   ? 'allowedChildren'
								   : 'allowedDescendants';

								$v = (substr($action, 0, 4) === 'deny') ? '0' : '1';

								foreach ($targets as $target)
								{
									// make sure the target really exists
									if (isset($tagConfig[$k][$target]))
									{
										$tagConfig[$k][$target] = $v;
									}
								}

								// We don't need those anymore
								unset($tagConfig['rules'][$action]);
								break;

							case 'requireParent':
							case 'requireAncestor':
								/**
								* Nothing to do here. If the target tag does not exist, this tag
								* will never be valid but we still leave it in the configuration
								*/
								break;

							default:
								// keep only the rules that target existing tags
								$targets = array_intersect_key($targets, $tagsConfig);
						}
					}
					unset($targets);

					/**
					* Remove rules with no targets
					*/
					$tagConfig['rules'] = array_filter($tagConfig['rules']);

					if (empty($tagConfig['rules']))
					{
						unset($tagConfig['rules']);
					}

					if (!empty($tagConfig['attrs']))
					{
						foreach ($tagConfig['attrs'] as &$attrConf)
						{
							/**
							* Remove the filterChain if it's empty
							*/
							if (empty($attrConf['filterChain']))
							{
								unset($attrConf['filterChain']);
							}
						}
						unset($attrConf);
					}
				}

				unset($tagConfig['defaultChildRule']);
				unset($tagConfig['defaultDescendantRule']);
				unset($tagConfig['disable']);

				/**
				* We only need to store this option if it's true
				*/
				if (!$tagConfig['disallowAsRoot'])
				{
					unset($tagConfig['disallowAsRoot']);
				}

				/**
				* We don't need the tag's template
				*/
				unset($tagConfig['xsl']);

				/**
				* Generate a proper (binary) bitfield
				*/
				$tagConfig['allowedChildren'] = self::bin2raw($tagConfig['allowedChildren']);
				$tagConfig['allowedDescendants'] = self::bin2raw($tagConfig['allowedDescendants']);

				/**
				* Children are descendants of current node, so we apply denyDescendant rules to them
				* as well.
				*/
				$tagConfig['allowedChildren'] &= $tagConfig['allowedDescendants'];
			}

			ksort($tagConfig);
		}
		unset($tagConfig);

		return $tagsConfig;
	}

	static protected function bin2raw($values)
	{
		$bin = implode('', $values) . str_repeat('0', (((count($values) + 7) & 7) ^ 7));

		return implode('', array_map('chr', array_map('bindec', array_map('strrev', str_split($bin, 8)))));
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
		// Start the stylesheet with boilerplate stuff and the /m template for rendering multiple
		// texts at once
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . '<xsl:output method="html" encoding="utf-8" indent="no"/>'
		     . '<xsl:template match="/m">'
		     . '<xsl:for-each select="*">'
		     . '<xsl:apply-templates/>'
		     . '<xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid"/></xsl:if>'
		     . '</xsl:for-each>'
		     . '</xsl:template>';

		// Append the tags' templates
		foreach ($this->tags as $tagName => $tag)
		{
			foreach ($tag->templates as $predicate => $template)
			{
				if ($predicate !== '')
				{
					$predicate = '[' . htmlspecialchars($predicate) . ']';
				}

				$xsl .= '<xsl:template match="' . $tagName . $predicate . '">'
				      . $template
				      . '</xsl:template>';
			}
		}

		// Append the plugins' XSL
		foreach ($this->getPlugins() as $plugin)
		{
			$xsl .= $plugin->getXSL();
		}

		// Append the templates for <st>, <et> and <i> nodes
		$xsl .= '<xsl:template match="st|et|i"/>';

		// Now close the stylesheet
		$xsl .= '</xsl:stylesheet>';

		/**
		* Build the DOM and prepare for some optimizations
		*/
		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		/**
		* Dedupes the templates
		*/
		$this->dedupeTemplates($dom);

		/**
		* Optimize templates attributes
		*/
		$this->optimizeXSLAttributes($dom);

		/**
		* If we're using the default prefix then we're done
		*/
		if ($prefix === 'xsl')
		{
			return rtrim($dom->saveXML());
		}

		/**
		* Fix the XSL prefix
		*/
		$trans = new DOMDocument;
		$trans->loadXML(
			'<?xml version="1.0" encoding="utf-8"?>
			<xsl:stylesheet version="1.0"' . $this->generateNamespaceDeclarations() . ' xmlns:' . $prefix . '="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="xml" encoding="utf-8" />

				<xsl:template match="xsl:*">
					<xsl:element name="' . $prefix . ':{local-name()}" namespace="http://www.w3.org/1999/XSL/Transform">
						<xsl:copy-of select="@*" />
						<xsl:apply-templates />
					</xsl:element>
				</xsl:template>

				<xsl:template match="node()">
					<xsl:copy>
						<xsl:copy-of select="@*" />
						<xsl:apply-templates />
					</xsl:copy>
				</xsl:template>

			</xsl:stylesheet>'
		);

		$xslt = new XSLTProcessor;
		$xslt->importStylesheet($trans);

		return rtrim($xslt->transformToXml($dom));
	}

	//==========================================================================
	// Javascript parser stuff
	//==========================================================================

	/**
	* Return the Javascript parser that corresponds to this configuration
	*
	* @param  array  $options Options to be passed to the JSParser generator
	* @return string
	*/
	public function getJSParser(array $options = array())
	{
		$jspg = new JSParserGenerator($this);

		return $jspg->get($options);
	}

	/**
	* Return JS parsers and their config
	*
	* @return array
	*/
	public function getJSPlugins()
	{
		$plugins = array();

		foreach ($this->getPluginsConfig('getJSConfig') as $pluginName => $pluginConfig)
		{
			$js = $this->$pluginName->getJSParser();

			if (!$js)
			{
				continue;
			}

			$plugins[$pluginName] = array(
				'parser' => $js,
				'config' => $pluginConfig,
				'meta'   => $this->$pluginName->getJSConfigMeta()
			);
		}

		return $plugins;
	}
}