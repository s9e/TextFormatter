<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException,
    RuntimeException,
    s9e\TextFormatter\ConfigBuilder\HTML5Helper,
    s9e\TextFormatter\ConfigBuilder\PredefinedTags,
    s9e\TextFormatter\ConfigBuilder\RegexpMaster,
    s9e\TextFormatter\ConfigBuilder\Tag,
    s9e\TextFormatter\ConfigBuilder\TemplateHelper;

class ConfigBuilder
{
	/**
	* @var array Tags repository (associative array of Tag objects)
	*/
	protected $tags = array();

	/**
	* @var array Registered namespaces
	*/
	protected $namespaces = array();

	/**
	* @var array Custom filters (array of Callback objects)
	*/
	protected $filters = array();

	//==========================================================================
	// Namespaces-related methods
	//==========================================================================

	/**
	* Register a new namespace
	*
	* @param  string $prefix Namespace prefix
	* @param  string $uri    Namespace URI
	*/
	public function registerNamespace($prefix, $uri)
	{
		if ($prefix === 'xsl'
		 || $uri === 'http://www.w3.org/1999/XSL/Transform')
		{
			 throw new InvalidArgumentException("Namespace prefix 'xsl' and namespace URI 'http://www.w3.org/1999/XSL/Transform' are reserved for internal use");
		}

		if (!$this->isValidNamespacePrefix($prefix))
		{
			throw new InvalidArgumentException("Invalid prefix name '" . $prefix . "'");
		}

		if (isset($this->namespaces[$prefix])
		 && $this->namespaces[$prefix] !== $uri)
		{
			throw new InvalidArgumentException("Prefix '" . $prefix . "' is already registered to namespace '" . $this->namespaces[$prefix] . "'");
		}

		$this->namespaces[$prefix] = $uri;
	}

	/**
	* Unregister a namespace if it exists
	*
	* @param  string $prefix Namespace prefix
	*/
	public function unregisterNamespace($prefix)
	{
		unset($this->namespaces[$prefix]);
	}

	/**
	* Return whether a namespace exists
	*
	* @param  string $prefix Namespace prefix
	* @return bool
	*/
	public function namespaceExists($prefix)
	{
		return isset($this->namespaces[$prefix]);
	}

	/**
	* Return whether a string is a valid namespace prefix
	*
	* @param  string $prefix Namespace prefix
	* @return bool
	*/
	public function isValidNamespacePrefix($prefix)
	{
		return preg_match('#^[a-z_][a-z_0-9]*$#Di', $prefix);
	}

	/**
	* Return the list of registered namespaces
	*
	* @return array
	*/
	public function getNamespaces()
	{
		return $this->namespaces;
	}

	/**
	* Return the URI associated with given namespace prefix
	*
	* @param  string $prefix Namespace prefix
	* @return mixed          Namespace URI, or FALSE if the namespace is not registered
	*/
	public function getNamespaceURI($prefix)
	{
		return (isset($this->namespaces[$prefix])) ? $this->namespaces[$prefix] : false;
	}

	/**
	* Return the first prefix associated with given namespace URI
	*
	* @param  string $uri Namespace URI
	* @return mixed       Namespace prefix, or FALSE if the namespace is not registered
	*/
	public function getNamespacePrefix($uri)
	{
		return array_search($uri, $this->namespaces, true);
	}

	/**
	* Return all the prefixes associated with given namespace URI
	*
	* @param  string $uri Namespace URI
	* @return array       List of namespace prefixes
	*/
	public function getNamespacePrefixes($uri)
	{
		return array_keys($this->namespaces, $uri, true);
	}

	//==========================================================================
	// Tags-related methods
	//==========================================================================

	/**
	* Add a new tag
	*
	* @param  string    $tagName Name of the tag
	* @param  Tag|array $tag     Tag to add, or array of tag options
	* @return Tag                Added tag
	*/
	public function addTag($tagName, $tag = array())
	{
		if (!($tag instanceof Tag))
		{
			$tag = new Tag($tag);
		}

		$tagName = Tag::normalizeName($tagName);

		if (isset($this->tag[$tagName]))
		{
			throw new InvalidArgumentException("Tag  '" . $tagName . "' already exists");
		}

		/**
		* Test for namespace prefix/existence
		*/
		$pos = strpos($tagName, ':');
		if ($pos !== false)
		{
			$prefix = substr($tagName, 0, $pos);

			if (!$this->namespaceExists($prefix))
			{
				throw new InvalidArgumentException("Namespace '" . $prefix . "' is not registered");
			}
		}

		$this->tags[$tagName] = $tag;

		return $tag;
	}

	/**
	* Return an existing tag
	*
	* @param  string $tagName
	* @return Tag
	*/
	public function getTag($tagName)
	{
		$tagName = Tag::normalizeTagName($tagName);

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Tag '" . $tagName . "' does not exist");
		}

		return $this->tags[$tagName];
	}

	/**
	* Remove a tag from the config
	*
	* @param string $tagName
	*/
	public function removeTag($tagName)
	{
		unset($this->tags[Tag::normalizeTagName($tagName)]);
	}

	/**
	* Return whether a tag exists
	*
	* @param  string $tagName
	* @return bool
	*/
	public function tagExists($tagName)
	{
		return isset($this->tags[Tag::normalizeTagName($tagName)]);
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
	// Filters
	//==========================================================================

	/**
	* Set a custom filter to be used to validate an attribute type
	*
	* Can be used to override the built-in filters, or support custom attribute types
	*
	* @param string   $filterName
	* @param Callback $callback
	*/
	public function setCustomFilter($filterName, Callback $callback)
	{
		// A hash sign # is prepended, because that's how built-in filters are detected
		$this->filters['#' . $filterName] = $callback;
	}

	/**
	* Return a custom filter's Callback object
	*
	* @param  string   $filterName
	* @return Callback
	*/
	public function getCustomFilter($filterName)
	{
		return $this->filters['#' . $filterName];
	}

	/**
	* Return all custom filters
	*
	* @return array
	*/
	public function getCustomFilters()
	{
		return $this->filters;
	}

	/**
	* Return whether a custom filter is set
	*
	* @return bool
	*/
	public function hasCustomFilter($filterName)
	{
		return isset($this->filters['#' . $filterName]);
	}

	/**
	* Unset custom filter
	*
	* @param string $filterName
	*/
	public function unsetCustomFilter($filterName)
	{
		unset($this->filters['#' . $filterName]);
	}

	//==========================================================================
	// Callbacks-related methods
	//==========================================================================

	/**
	* Normalize the representation of a callback
	*
	* This method will return an array with 1 to 3 components:
	*  - callback: the actual callback (required)
	*  - params:   the list of params that must be passed to the callback (optional)
	*  - js:       the Javascript source representing this callback
	*
	* There is, however, one exception; Validators, such as "#int" or "#url" are returned as
	* strings. If the passed callback isn't callable and isn't a validator, an exception is thrown.
	*
	* @param  mixed        $callback
	* @return string|array
	*/
	protected function normalizeCallback($callback)
	{
		if ($callback instanceof Callback)
		{
			return $callback->toArray();
		}

		if (is_string($callback) && $callback[0] === '#')
		{
			// It's a built-in filter, return as-is
			return $callback;
		}

		if (is_callable($callback))
		{
			// It's a callback with no signature, we'll assume it just requires the attribute's
			// value. Otherwise, a Callback object should be used instead
			return array(
				'callback' => $callback,
				'params'   => array('attrVal' => null)
			);
		}

		throw new InvalidArgumentException("Callback '" . var_export($callback, true) . "' is not callable");
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
		if (isset($this->namespaces[$prefix]))
		{
			throw new InvalidArgumentException("Prefix '" . $prefix . "' is already registered to namespace '" . $this->namespaces[$prefix] . "'");
		}

		/**
		* Build the stylesheet
		*/
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . "\n"
		     . '<xsl:stylesheet version="1.0"' . $this->generateNamespaceDeclarations() . '>'
		     . '<xsl:output method="html" encoding="utf-8" indent="no"/>'
		     . '<xsl:template match="/m">'
		     . '<xsl:for-each select="*">'
		     . '<xsl:apply-templates/>'
		     . '<xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid"/></xsl:if>'
		     . '</xsl:for-each>'
		     . '</xsl:template>';

		foreach ($this->tags as $tag)
		{
			if (isset($tag['xsl']))
			{
				$xsl .= $tag['xsl'];
			}
		}

		$xsl .= $this->xsl
		      . '<xsl:template match="st|et|i"/>'
		      . '</xsl:stylesheet>';

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

	/**
	* Add generic XSL
	*
	* This XSL will be output in the final stylesheet before tag-specific templates.
	*
	* @param string  $xsl     Must be valid XSL elements. A root node is not required
	* @param integer $flags
	*/
	public function addXSL($xsl, $flags = 0)
	{
		$this->xsl .= $this->normalizeXSL($xsl, $flags);
	}

	/**
	* Generate the namespace declarations of all registered namespaces plus the XSL namespace
	*
	* @return string
	*/
	protected function generateNamespaceDeclarations()
	{
		$str = ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		foreach ($this->namespaces as $prefix => $uri)
		{
			$str .= ' xmlns:' . $prefix . '="' . htmlspecialchars($uri) . '"';
		}

		return $str;
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