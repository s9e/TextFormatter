<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument,
    DOMXPath,
    InvalidArgumentException,
    RuntimeException,
    SimpleXMLElement,
    UnexpectedValueException,
    XSLTProcessor;

class ConfigBuilder
{
	/**
	* Allow user-supplied data to be used in sensitive area of a template
	* @see self::setTagTemplate()
	*/
	const ALLOW_UNSAFE_TEMPLATES = 1;

	/**
	* Whether or not preserve redundant whitespace in a template
	* @see  self::setTagTemplate()
	* @link http://www.php.net/manual/en/class.domdocument.php#domdocument.props.preservewhitespace
	*/
	const PRESERVE_WHITESPACE = 2;

	/**
	* @var array Tags repository
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

	/**
	* @var array List of allowed schemes (used in URL filter)
	*/
	protected $allowedSchemes = array('http', 'https');

	/**
	* @var array List of disallowed hosts (used in URL filter)
	*/
	protected $disallowedHosts = array();

	/**
	* @var array List of hosts whose URL we check for redirects (used in URL filter)
	*/
	protected $resolveRedirectsHosts = array();

	/**
	* @var string Default scheme to be used when validating schemeless URLs
	*/
	protected $defaultScheme;

	/**
	* @var string Extra XSL to append to the stylesheet
	*/
	protected $xsl = '';

	/**
	* @var array  Default options applied to tags
	*/
	public $defaultTagOptions = array(
		'disable'        => false,
		'disallowAsRoot' => false,
		'tagLimit'       => 100,
		'nestingLimit'   => 10,
		'defaultChildRule'      => 'allow',
		'defaultDescendantRule' => 'allow'
	);

	/**
	* @var array  Default options applied to attributes
	*/
	public $defaultAttributeOptions = array(
		'filterChain' => array(),
		'required'    => true
	);

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
	* Define a new tag
	*
	* @param string $tagName    Name of the tag {@see isValidTagName()}
	* @param array  $tagOptions Tag options (automatically augmented by $this->defaultTagOptions)
	*/
	public function addTag($tagName, array $tagOptions = array())
	{
		$tagName = $this->normalizeTagName($tagName, false);

		if (isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Tag '" . $tagName . "' already exists");
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

		/**
		* Create the tag using the default options
		*/
		$this->tags[$tagName] = $this->defaultTagOptions;

		/**
		* Set the user-supplied options
		*/
		$this->setTagOptions($tagName, $tagOptions);
	}

	/**
	* Remove a tag from the config
	*
	* @param string $tagName
	*/
	public function removeTag($tagName)
	{
		unset($this->tags[$this->normalizeTagName($tagName)]);
	}

	/**
	* Return whether a tag exists
	*
	* @param  string $tagName
	* @return bool
	*/
	public function tagExists($tagName)
	{
		return isset($this->tags[$this->normalizeTagName($tagName, false)]);
	}

	/**
	* Return whether a string is a valid tag name
	*
	* @param  string $tagName
	* @return bool
	*/
	public function isValidTagName($tagName)
	{
		/**
		* If the tag's name is prefixed, test the prefix then remove it along with the colon from
		* the tag's name
		*/
		$pos = strpos($tagName, ':');

		if ($pos !== false)
		{
			if (!$this->isValidNamespacePrefix(substr($tagName, 0, $pos)))
			{
				return false;
			}

			$tagName = substr($tagName, $pos + 1);
		}

		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $tagName);
	}

	/**
	* Validate and normalize a tag name
	*
	* @param  string $tagName   Original tag name
	* @param  bool   $mustExist If TRUE, throw an exception if the tag does not exist
	* @return string            Normalized tag name, in uppercase
	*/
	public function normalizeTagName($tagName, $mustExist = true)
	{
		if (!$this->isValidTagName($tagName))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $tagName . "'");
		}

		if (strpos($tagName, ':') === false)
		{
			$tagName = strtoupper($tagName);
		}

		if ($mustExist && !isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Tag '" . $tagName . "' does not exist");
		}

		return $tagName;
	}

	//==========================================================================
	// Tag options-related methods
	//==========================================================================

	/**
	* Get all of a tag's options
	*
	* @param  string $tagName
	* @return array
	*/
	public function getTagOptions($tagName)
	{
		$tagName = $this->normalizeTagName($tagName);

		return $this->tags[$tagName];
	}

	/**
	* Get a tag's option
	*
	* @param  string $tagName
	* @param  string $optionName
	* @return mixed
	*/
	public function getTagOption($tagName, $optionName)
	{
		$tagName = $this->normalizeTagName($tagName);

		if (!array_key_exists($optionName, $this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown option '" . $optionName . "' from tag '" . $tagName . "'");
		}

		return $this->tags[$tagName][$optionName];
	}

	/**
	* Set several options for a tag
	*
	* @param string $tagName
	* @param array  $tagOptions
	*/
	public function setTagOptions($tagName, array $tagOptions)
	{
		foreach ($tagOptions as $optionName => $optionValue)
		{
			$this->setTagOption($tagName, $optionName, $optionValue);
		}
	}

	/**
	* Set a tag's option
	*
	* @param string $tagName
	* @param string $optionName
	* @param mixed  $optionValue
	*/
	public function setTagOption($tagName, $optionName, $optionValue)
	{
		$tagName = $this->normalizeTagName($tagName);

		switch ($optionName)
		{
			case 'attrs':
				foreach ($optionValue as $attrName => $attrConf)
				{
					$this->addAttribute($tagName, $attrName, $attrConf);
				}
				break;

			case 'rules':
				foreach ($optionValue as $action => $targets)
				{
					foreach ($targets as $target)
					{
						$this->addTagRule($tagName, $action, $target);
					}
				}
				break;

			case 'template':
				$this->setTagTemplate($tagName, $optionValue);
				break;

			case 'xsl':
				$this->setTagXSL($tagName, $optionValue);
				break;

			default:
				if (isset($this->defaultTagOptions[$optionName]))
				{
					/**
					* Preserve the PHP type of that option, if applicable
					*/
					settype($optionValue, gettype($this->defaultTagOptions[$optionName]));
				}

				$this->tags[$tagName][$optionName] = $optionValue;
		}
	}

	//==========================================================================
	// Attributes-related methods
	//==========================================================================

	/**
	* Define an attribute for a tag
	*
	* @param string       $tagName  Name of the tag
	* @param string       $attrName Name of the attribute
	* @param array|string $attrConf If array: attribute options, if string: name of the filter used
	*                               for the attribute's filterChain
	*/
	public function addAttribute($tagName, $attrName, $attrConf = array())
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		if (isset($this->tags[$tagName]['attrs'][$attrName]))
		{
			throw new InvalidArgumentException("Attribute '" . $attrName . "' already exists");
		}

		// Create the attribute with default config values
		$this->tags[$tagName]['attrs'][$attrName] = $this->defaultAttributeOptions;

		// If $attrConf is a string, we use the built-in filter of the same name as its filter
		if (is_string($attrConf))
		{
			$attrConf = array('filter' => $attrConf);
		}

		// Now set the user-defined options
		$this->setAttributeOptions($tagName, $attrName, $attrConf);
	}

	/**
	* Set several options in a tag's attribute config
	*
	* @param string $tagName
	* @param string $attrName
	* @param array  $options
	*/
	public function setAttributeOptions($tagName, $attrName, $options)
	{
		foreach ($options as $optionName => $optionValue)
		{
			$this->setAttributeOption($tagName, $attrName, $optionName, $optionValue);
		}
	}

	/**
	* Set an option in a tag's attribute config
	*
	* @param string $tagName
	* @param string $attrName
	* @param string $optionName
	* @param mixed  $optionValue
	*/
	public function setAttributeOption($tagName, $attrName, $optionName, $optionValue)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		$attrConf =& $this->tags[$tagName]['attrs'][$attrName];

		switch ($optionName)
		{
			case 'filter':
				$optionValue = array($optionValue);
				// no break; here

			case 'filterChain':
				$this->clearAttributeFilterChain($tagName, $attrName);

				foreach ($optionValue as $callback)
				{
					$this->appendAttributeFilter($tagName, $attrName, $callback);
				}
				break;

			default:
				if (isset($this->defaultAttributeOptions[$optionName]))
				{
					/**
					* Preserve the PHP type of that option, if applicable
					*/
					settype($optionValue, gettype($this->defaultAttributeOptions[$optionName]));
				}

				$attrConf[$optionName] = $optionValue;
		}
	}

	/**
	* Return all the options of a tag's attribute
	*
	* @param  string $tagName
	* @param  string $attrName
	* @return array
	*/
	public function getAttributeOptions($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		return $this->tags[$tagName]['attrs'][$attrName];
	}

	/**
	* Return the value of an option in a tag's attribute config
	*
	* @param  string $tagName
	* @param  string $attrName
	* @param  string $optionName
	* @return mixed
	*/
	public function getAttributeOption($tagName, $attrName, $optionName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		return $this->tags[$tagName]['attrs'][$attrName][$optionName];
	}

	/**
	* Remove an attribute from a tag
	*
	* @param string $tagName
	* @param string $attrName
	*/
	public function removeAttribute($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);
		unset($this->tags[$tagName]['attrs'][$attrName]);
	}

	/**
	* Return whether a tag's attribute exists
	*
	* @param  string $tagName
	* @param  string $attrName
	* @return bool
	*/
	public function attributeExists($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		return isset($this->tags[$tagName]['attrs'][$attrName]);
	}

	/**
	* Return whether a string is a valid attribute name
	*
	* @param  string $attrName
	* @return bool
	*/
	public function isValidAttributeName($attrName)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9\\-]*$#Di', $attrName);
	}

	/**
	* Validate and normalize an attribute name
	*
	* @param  string $attrName Original attribute name
	* @param  string $tagName  If set, check that the attribute exists for given tag and throw an
	*                          exception otherwise
	* @return string           Normalized attribute name, in lowercase
	*/
	public function normalizeAttributeName($attrName, $tagName = null)
	{
		if (!$this->isValidAttributeName($attrName))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $attrName . "'");
		}

		$attrName = strtolower($attrName);

		if (isset($tagName))
		{
			$tagName = $this->normalizeTagName($tagName);

			if (!isset($this->tags[$tagName]['attrs'][$attrName]))
			{
				throw new InvalidArgumentException("Tag '" . $tagName . "' does not have an attribute named '" . $attrName . "'");
			}
		}

		return $attrName;
	}

	/**
	* Remove all filters from an attribute's filter chain
	*
	* @param string $tagName
	* @param string $attrName
	*/
	public function clearAttributeFilterChain($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		$this->tags[$tagName]['attrs'][$attrName]['filterChain'] = array();
	}

	/**
	* Append a filter to an attribute's filter chain
	*
	* @param string          $tagName
	* @param string          $attrName
	* @param string|Callback $callback
	*/
	public function appendAttributeFilter($tagName, $attrName, $callback)
	{
		$this->addAttributeFilter('array_push', $tagName, $attrName, $callback);
	}

	/**
	* prepend a filter to an attribute's filter chain
	*
	* @param string          $tagName
	* @param string          $attrName
	* @param string|Callback $callback
	*/
	public function prependAttributeFilter($tagName, $attrName, $callback)
	{
		$this->addAttributeFilter('array_unshift', $tagName, $attrName, $callback);
	}

	/**
	* Append a filter to an attribute's filter chain
	*
	* @param string          $func     Either "array_push" or "array_unshift"
	* @param string          $tagName
	* @param string|Callback $callback
	*/
	protected function addAttributeFilter($func, $tagName, $attrName, $callback)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);
		$callback = $this->normalizeCallback($callback);

		$func($this->tags[$tagName]['attrs'][$attrName]['filterChain'], $callback);
	}

	/**
	* Add an attribute parser
	*
	* @param string $tagName
	* @param string $attrName
	* @param string $regexp
	*/
	public function addAttributeParser($tagName, $attrName, $regexp)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		$this->tags[$tagName]['attributeParsers'][$attrName][] = $regexp;
	}

	/**
	* Test whether any attribute parsers or a specific attribute parser exist
	*
	* @param  string $tagName
	* @param  string $attrName
	* @param  string $regexp   If set, return TRUE only if this regexp is an attribute parser
	* @return bool
	*/
	public function attributeParserExists($tagName, $attrName, $regexp = null)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		if (isset($this->tags[$tagName]['attributeParsers'][$attrName]))
		{
			if (isset($regexp))
			{
				return in_array($regexp, $this->tags[$tagName]['attributeParsers'][$attrName]);
			}

			return true;
		}

		return false;
	}

	/**
	* Remove all attribute parsers of a given name
	*
	* @param string $tagName
	* @param string $attrName
	*/
	public function clearAttributeParsers($tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		unset($this->tags[$tagName]['attributeParsers'][$attrName]);

		if (empty($this->tags[$tagName]['attributeParsers']))
		{
			unset($this->tags[$tagName]['attributeParsers']);
		}
	}

	//==========================================================================
	// Rules-related methods
	//==========================================================================

	/**
	* Define a rule
	*
	* The first tag must already exist at the time the rule is created.
	* The target tag doesn't have to exist though, so that we can set all the rules related to a tag
	* during its creation, regardless on whether target tags exist or not. Rules that pertain to
	* inexistent tags do not appear in the final configuration.
	*
	* @param string $tagName
	* @param string $action
	* @param string $target
	*/
	public function addTagRule($tagName, $action, $target)
	{
		$tagName = $this->normalizeTagName($tagName);
		$target  = $this->normalizeTagName($target, false);

		if (!in_array($action, array(
			'allowChild',
			'allowDescendant',
			'closeParent',
			'closeAncestor',
			'denyChild',
			'denyDescendant',
			'reopenChild',
			'requireParent',
			'requireAncestor'
		), true))
		{
			throw new UnexpectedValueException("Unknown rule action '" . $action . "'");
		}

		$this->tags[$tagName]['rules'][$action][$target] = $target;
	}

	/**
	* Remove a tag's rule
	*
	* @param string $tagName
	* @param string $action
	* @param string $target
	*/
	public function removeRule($tagName, $action, $target)
	{
		$tagName = $this->normalizeTagName($tagName);
		$target  = $this->normalizeTagName($target);

		unset($this->tags[$tagName]['rules'][$action][$target]);
	}

	//==========================================================================
	// Tag templates-related methods
	//==========================================================================

	/**
	* Return the XSL associated with a tag
	*
	* @param  string $tagName Name of the tag
	* @return string
	*/
	public function getTagXSL($tagName)
	{
		$tagName = $this->normalizeTagName($tagName);

		if (!isset($this->tags[$tagName]['xsl']))
		{
			throw new InvalidArgumentException("No XSL set for tag '" . $tagName . "'");
		}

		return $this->tags[$tagName]['xsl'];
	}

	/**
	* Set the template associated with a tag
	*
	* @param string  $tagName Name of the tag
	* @param string  $tpl     Must be the contents of a valid <xsl:template> element
	* @param integer $flags
	*/
	public function setTagTemplate($tagName, $tpl, $flags = 0)
	{
		$tagName = $this->normalizeTagName($tagName);

		$xsl = '<xsl:template match="' . $tagName . '">'
		     . $tpl
		     . '</xsl:template>';

		$this->setTagXSL($tagName, $xsl, $flags);
	}

	/**
	* Set or replace the XSL associated with a tag
	*
	* @param string  $tagName Name of the tag
	* @param string  $xsl     Must be valid XSL elements. A root node is not required
	* @param integer $flags
	*/
	public function setTagXSL($tagName, $xsl, $flags = 0)
	{
		$tagName = $this->normalizeTagName($tagName);

		$this->tags[$tagName]['xsl'] = $this->normalizeXSL($xsl, $flags);
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

		throw new RuntimeException('Undefined property: ' . __CLASS__ . '::$' . $k);
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
			throw new InvalidArgumentException('Invalid plugin name "' . $pluginName . '"');
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
	// URL config options
	//==========================================================================

	/**
	* Allow a URL scheme
	*
	* @param string $scheme URL scheme, e.g. "file" or "ed2k"
	*/
	public function allowScheme($scheme)
	{
		$scheme = $this->normalizeScheme($scheme);

		$this->allowedSchemes[] = $scheme;
	}

	/**
	* Set a default scheme to be used for validation of scheme-less URLs
	*
	* @param string $scheme URL scheme, e.g. "http" or "https"
	*/
	public function setDefaultScheme($scheme)
	{
		$this->defaultScheme = $scheme;
	}

	/**
	* Validate and normalize a scheme name to lowercase, or throw an exception if invalid
	*
	* @link http://tools.ietf.org/html/rfc3986#section-3.1
	*
	* @param  string $scheme URL scheme, e.g. "file" or "ed2k"
	* @return string
	*/
	protected function normalizeScheme($scheme)
	{
		if (!preg_match('#^[a-z][a-z0-9+\\-.]*$#Di', $scheme))
		{
			throw new InvalidArgumentException("Invalid scheme name '" . $scheme . "'");
		}

		return strtolower($scheme);
	}

	/**
	* Return the list of allowed URL schemes
	*
	* @return array
	*/
	public function getAllowedSchemes()
	{
		return $this->allowedSchemes;
	}

	/**
	* Disallow a hostname (or hostname mask) from being used in URLs
	*
	* @param string $host Hostname or hostmask
	*/
	public function disallowHost($host)
	{
		$this->disallowedHosts[] = $this->normalizeHostmask($host);
	}

	/**
	* Force URLs from given hostmask to be followed and resolved to their true location
	*
	* @param string $host Hostname or hostmask
	*/
	public function resolveRedirectsFrom($host)
	{
		$this->resolveRedirectsHosts[] = $this->normalizeHostmask($host);
	}

	/**
	* @param  string $host Hostname or hostmask
	* @return string
	*/
	protected function normalizeHostmask($host)
	{
		if (preg_match('#[\\x80-\xff]#', $host))
		{
			// @codeCoverageIgnoreStart
			if (!function_exists('idn_to_ascii'))
			{
				throw new RuntimeException('Cannot handle IDNs without the Intl PHP extension');
			}
			// @codeCoverageIgnoreEnd

			$host = idn_to_ascii($host);
		}

		/**
		* Transform "*.tld" and ".tld" into the functionally equivalent "tld"
		*
		* As a side-effect, when someone bans *.example.com it also bans example.com (no subdomain)
		* but that's usually what people were trying to achieve.
		*/
		$host = ltrim($host, '*.');

		return $host;
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
	* Return the URL-specific configuration options
	*
	* @return array
	*/
	public function getUrlConfig()
	{
		$rm = $this->getRegexpMaster();

		$urlConfig = array(
			'allowedSchemes' => '#^' . $rm->buildRegexpFromList($this->allowedSchemes) . '$#Di'
		);

		if (isset($this->defaultScheme))
		{
			$urlConfig['defaultScheme'] = $this->defaultScheme;
		}

		foreach (array('disallowedHosts', 'resolveRedirectsHosts') as $k)
		{
			if (empty($this->$k))
			{
				continue;
			}

			$regexp = $rm->buildRegexpFromList(
				$this->$k,
				// Asterisks * are turned into a catch-all expression
				array('specialChars' => array('*' => '.*'))
			);

			$urlConfig[$k] = '#(?<![^\\.])' . $regexp . '$#DiS';
		}

		return $urlConfig;
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

	/**
	* Normalize XSL
	*
	* Check for well-formedness, remove whitespace if applicable.
	* Check for unsafe script tags.
	*
	* @param  string  $xsl     Must be valid XSL elements. A root node is not required
	* @param  integer $flags
	* @return string
	*/
	protected function normalizeXSL($xsl, $flags)
	{
		/**
		* Prepare a temporary stylesheet so that we can load the template and make sure it's valid
		*/
		$xsl = '<xsl:stylesheet' . $this->generateNamespaceDeclarations() . '>'
		     . $xsl
		     . '</xsl:stylesheet>';

		/**
		* Load the stylesheet with libxml's internal errors temporarily enabled
		*/
		$useInternalErrors = libxml_use_internal_errors(true);

		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = (bool) ($flags & self::PRESERVE_WHITESPACE);
		$res = $dom->loadXML($xsl);

		libxml_use_internal_errors($useInternalErrors);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Invalid XML - error was: ' . $error->message);
		}

		if (!($flags & self::ALLOW_UNSAFE_TEMPLATES))
		{
			$xpath = new DOMXPath($dom);

			$hasUnsafeScript = (bool) $xpath->evaluate(
				'count(
					//*[translate(name(), "SCRIPT", "script") = "script"]
					   [
					       @*[translate(name(), "SRC", "src") = "src"][contains(., "{")]
					    or .//xsl:value-of
					    or .//xsl:attribute
					   ]
				)'
			);

			if ($hasUnsafeScript)
			{
				throw new RuntimeException('It seems that your template contains a <script> tag that uses user-supplied information. Those can be unsafe and are disabled by default. Please use the ' . __CLASS__ . '::ALLOW_UNSAFE_TEMPLATES flag to enable it');
			}

			if ($xpath->evaluate('count(//@disable-output-escaping)'))
			{
				throw new RuntimeException("It seems that your template contains a 'disable-output-escaping' attribute. Those can be unsafe and are disabled by default. Please use the " . __CLASS__ . "::ALLOW_UNSAFE_TEMPLATES flag to enable it");
			}

			$attrs = $xpath->query(
				'//@*[starts-with(translate(name(), "ON", "on"), "on")][contains(., "{")]'
			);

			foreach ($attrs as $attr)
			{
				// test for false-positives, IOW escaped brackets
				preg_match_all('#\\{.#', $attr->value, $matches);

				foreach ($matches[0] as $m)
				{
					if ($m !== '{{')
					{
						throw new RuntimeException("It seems that your template contains at least one attribute named '" . $attr->name . "' using user-supplied content. Those can be unsafe and are disabled by default. Please use the " . __CLASS__ . "::ALLOW_UNSAFE_TEMPLATES flag to enable it");
					}
				}
			}

			$attrs = $xpath->query(
				// any xsl:attribute node that whose @name starts with "on" and has an
				// xsl:value-of or xsl:templates descendant
				'//xsl:attribute
					[starts-with(translate(@name, "ON", "on"), "on")]
					[//xsl:value-of or //xsl:apply-templates]'
			);

			foreach ($attrs as $attr)
			{
				throw new RuntimeException("It seems that your template contains at least one attribute named '" . $attr->getAttribute('name') . "' that is created dynamically. Those can be unsafe and are disabled by default. Please use the " . __CLASS__ . "::ALLOW_UNSAFE_TEMPLATES flag to enable it");
			}
		}

		/**
		* Rebuild the XSL by serializing and concatenating each of the root node's children
		*/
		$xsl = '';
		foreach ($dom->documentElement->childNodes as $childNode)
		{
			$xsl .= $dom->saveXML($childNode);
		}

		return $xsl;
	}

	/**
	* Merge identical templates together
	*
	* Works by grouping templates by their content (using a simple text comparison) then it
	* generates a new template with a merged @match clause and it removes the old templates.
	*
	* @param DOMDocument $dom
	*/
	protected function dedupeTemplates(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);
		$dupes = array();

		foreach ($xpath->query('/xsl:stylesheet/xsl:template[@match]') as $node)
		{
			// Make a copy of the template node so that we can remove its @match
			$tmp = $node->cloneNode(true);
			$tmp->removeAttribute('match');

			$xml = $dom->saveXML($tmp);

			if (isset($dupes[$xml]))
			{
				// It's a dupe, append its @match to the original template's @match
				$dupes[$xml]->setAttribute(
					'match',
					$dupes[$xml]->getAttribute('match') . '|' . $node->getAttribute('match')
				);

				// ...then remove the dupe from the template
				$node->parentNode->removeChild($node);
			}
			else
			{
				// Not a dupe, save the node for later
				$dupes[$xml] = $node;
			}
		}
		unset($dupes);
	}


	/**
	* Optimize attribute setting
	*
	* Will replace <xsl:attribute/> nodes with inline attributes wherever applicable.
	* Also will replace conditional attributes with a <xsl:copy-of/>, e.g.
	*	<xsl:if test="@foo">
	*		<xsl:attribute name="foo">
	*			<xsl:value-of select="@foo" />
	*		</xsl:attribute>
	*	</xsl:if>
	* into
	*	<xsl:copy-of select="@foo"/>
	*
	* @param DOMDocument $dom
	*/
	protected function optimizeXSLAttributes(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		// Inline attributes
		$query = 'xsl:template[@match]'
		       . '//*[namespace-uri() = ""]'
		       . '/xsl:attribute[count(descendant::node()) = 1]'
		       . '/xsl:value-of[@select]';

		foreach ($xpath->query($query) as $valueOf)
		{
			$attribute = $valueOf->parentNode;

			$attribute->parentNode->setAttribute(
				$attribute->getAttribute('name'),
				'{' . $valueOf->getAttribute('select') . '}'
			);

			$attribute->parentNode->removeChild($attribute);
		}

		// Replace conditional attributes with <xsl:copy-of/>
		$query = 'xsl:template[@match]'
		       . '//xsl:if'
		       . "[starts-with(@test, '@')]"
		       . '[count(descendant::node()) = 2]'
		       . '[xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]';

		foreach ($xpath->query($query) as $if)
		{
			$copyOf = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:copy-of');
			$copyOf->setAttribute('select', $if->getAttribute('test'));

			$if->parentNode->replaceChild($copyOf, $if);
		}

		// Find simple templates
		$query = 'xsl:template[@match][not(@mode)]'
		       . '[count(descendant::node()) = 2]'
		       . '[*[not(@*)][name() = translate(../@match, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")][xsl:apply-templates[not(@*)]]]';

		$templates = array();
		foreach ($xpath->query($query) as $template)
		{
			$templates[] = $template;
		}

		// We just need to replace two templates to save at least 3 bytes but we'll only apply this
		// optimization if can replace at least three templates
		if (isset($templates[2]))
		{
			$names = array();

			foreach ($templates as $template)
			{
				$names[] = $template->getAttribute('match');

				$template->parentNode->removeChild($template);
			}

			$chars = preg_replace('#[^A-Z]+#', '', count_chars(implode('', $names), 3));

			$template = $dom->createElementNS(
				'http://www.w3.org/1999/XSL/Transform',
				'xsl:template'
			);
			$template->setAttribute('match', implode('|', $names));

			$element = $dom->createElementNS(
				'http://www.w3.org/1999/XSL/Transform',
				'xsl:element'
			);
			$element->setAttribute(
				'name',
				"{translate(name(),'" . $chars . "','" . strtolower($chars) . "')}"
			);

			$dom->documentElement
			    ->appendChild($template)
			    ->appendChild($element)
			    ->appendChild($dom->createElementNS(
					'http://www.w3.org/1999/XSL/Transform',
					'xsl:apply-templates'
			    ));
		}
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

	//==========================================================================
	// HTML guessing stuff
	//==========================================================================

	/**
	* What is this? you might ask. This is basically a compressed version of the HTML5 content
	* models, with some liberties taken.
	*
	* For each element, up to three bitfields are defined: "c", "ac" and "dd". Bitfields are stored
	* as a number for convenience.
	*
	* "c" represents the categories the element belongs to. The categories are comprised of HTML5
	* content models (such as "phrasing content" or "interactive content") plus a few special
	* categories created dynamically (part of the specs refer to "a group of X and Y elements"
	* rather than a specific content model, in which case a special category is formed for those
	* elements.)
	*
	* "ac" represents the categories that are allowed as children of given element.
	*
	* "dd" represents the categories that may not appear as a descendant of given element.
	*
	* Sometimes, HTML5 specifies some restrictions on when an element can accept certain children,
	* or what categories the element belongs to. For example, an <img> element is only part of the
	* "interactive content" category if it has a "usemap" attribute. Those restrictions are
	* expressed as an XPath expression and stored using the concatenation of the key of the bitfield
	* plus the bit number of the category. For instance, if "interactive content" got assigned to
	* bit 2, the definition of the <img> element will contain a key "c2" with value "@usemap".
	*
	* There is a special content model defined in HTML5, the "transparent" content model. If an
	* element uses the "transparent" content model, the key "t" is non-empty (set to 1.)
	*
	* In addition, HTML5 defines "optional end tag" rules, where one element automatically closes
	* its predecessor. Those are used to generate closeParent rules and are stored in the "cp" key.
	*/
	protected $htmlElements = array(
		'a'=>array('c'=>15,'ac'=>0,'dd'=>8,'t'=>1),
		'abbr'=>array('c'=>7,'ac'=>4),
		'address'=>array('c'=>1027,'ac'=>1,'dd'=>1552,'cp'=>array('p')),
		'area'=>array('c'=>5),
		'article'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'aside'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'audio'=>array('c'=>47,'c3'=>'@controls','c1'=>'@controls','ac'=>8192,'ac13'=>'@src','t'=>1),
		'b'=>array('c'=>7,'ac'=>4),
		'bdi'=>array('c'=>7,'ac'=>4),
		'bdo'=>array('c'=>7,'ac'=>4),
		'blockquote'=>array('c'=>259,'ac'=>1,'cp'=>array('p')),
		'br'=>array('c'=>5),
		'button'=>array('c'=>15,'ac'=>4,'dd'=>8),
		'canvas'=>array('c'=>39,'ac'=>0,'t'=>1),
		'caption'=>array('c'=>64,'ac'=>1,'dd'=>4194304),
		'cite'=>array('c'=>7,'ac'=>4),
		'code'=>array('c'=>7,'ac'=>4),
		'col'=>array('c'=>268435456,'c28'=>'not(@span)'),
		'colgroup'=>array('c'=>64,'ac'=>268435456,'ac28'=>'not(@span)'),
		'datalist'=>array('c'=>5,'ac'=>1048580),
		'dd'=>array('c'=>131072,'ac'=>1,'cp'=>array('dd','dt')),
		'del'=>array('c'=>5,'ac'=>0,'t'=>1),
		'details'=>array('c'=>267,'ac'=>524289),
		'dfn'=>array('c'=>134217735,'ac'=>4,'dd'=>134217728),
		'div'=>array('c'=>3,'ac'=>1,'cp'=>array('p')),
		'dl'=>array('c'=>3,'ac'=>131072,'cp'=>array('p')),
		'dt'=>array('c'=>131072,'ac'=>1,'dd'=>16912,'cp'=>array('dd','dt')),
		'em'=>array('c'=>7,'ac'=>4),
		'embed'=>array('c'=>47),
		'fieldset'=>array('c'=>259,'ac'=>2097153,'cp'=>array('p')),
		'figcaption'=>array('c'=>0x80000000,'ac'=>1),
		'figure'=>array('c'=>259,'ac'=>0x80000001),
		'footer'=>array('c'=>17411,'ac'=>1,'dd'=>16384,'cp'=>array('p')),
		'form'=>array('c'=>67108867,'ac'=>1,'dd'=>67108864,'cp'=>array('p')),
		'h1'=>array('c'=>147,'ac'=>4,'cp'=>array('p')),
		'h2'=>array('c'=>147,'ac'=>4,'cp'=>array('p')),
		'h3'=>array('c'=>147,'ac'=>4,'cp'=>array('p')),
		'h4'=>array('c'=>147,'ac'=>4,'cp'=>array('p')),
		'h5'=>array('c'=>147,'ac'=>4,'cp'=>array('p')),
		'h6'=>array('c'=>147,'ac'=>4,'cp'=>array('p')),
		'header'=>array('c'=>17411,'ac'=>1,'dd'=>16384,'cp'=>array('p')),
		'hgroup'=>array('c'=>19,'ac'=>128,'cp'=>array('p')),
		'hr'=>array('c'=>1,'cp'=>array('p')),
		'i'=>array('c'=>7,'ac'=>4),
		'img'=>array('c'=>47,'c3'=>'@usemap'),
		'input'=>array('c'=>15,'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"'),
		'ins'=>array('c'=>7,'ac'=>0,'t'=>1),
		'kbd'=>array('c'=>7,'ac'=>4),
		'keygen'=>array('c'=>15),
		'label'=>array('c'=>33554447,'ac'=>4,'dd'=>33554432),
		'legend'=>array('c'=>2097152,'ac'=>4),
		'li'=>array('c'=>1073741824,'ac'=>1,'cp'=>array('li')),
		'map'=>array('c'=>7,'ac'=>0,'t'=>1),
		'mark'=>array('c'=>7,'ac'=>4),
		'menu'=>array('c'=>11,'c3'=>'@type="toolbar"','c1'=>'@type="toolbar" or @type="list"','ac'=>1073741825,'cp'=>array('p')),
		'meter'=>array('c'=>16779271,'ac'=>4,'dd'=>16777216),
		'nav'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'object'=>array('c'=>47,'c3'=>'@usemap','ac'=>8388608,'t'=>1),
		'ol'=>array('c'=>3,'ac'=>1073741824,'cp'=>array('p')),
		'optgroup'=>array('c'=>4096,'ac'=>1048576,'cp'=>array('optgroup','option')),
		'option'=>array('c'=>1052672,'cp'=>array('option')),
		'output'=>array('c'=>7,'ac'=>4),
		'p'=>array('c'=>3,'ac'=>4,'cp'=>array('p')),
		'param'=>array('c'=>8388608),
		'pre'=>array('c'=>3,'ac'=>4,'cp'=>array('p')),
		'progress'=>array('c'=>264199,'ac'=>4,'dd'=>262144),
		'q'=>array('c'=>7,'ac'=>4),
		'rp'=>array('c'=>65536,'ac'=>4,'cp'=>array('rp','rt')),
		'rt'=>array('c'=>65536,'ac'=>4,'cp'=>array('rp','rt')),
		'ruby'=>array('c'=>7,'ac'=>65540),
		's'=>array('c'=>7,'ac'=>4),
		'samp'=>array('c'=>7,'ac'=>4),
		'section'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'select'=>array('c'=>15,'ac'=>4096),
		'small'=>array('c'=>7,'ac'=>4),
		'source'=>array('c'=>8192,'c13'=>'not(@src)'),
		'span'=>array('c'=>7,'ac'=>4),
		'strong'=>array('c'=>7,'ac'=>4),
		'sub'=>array('c'=>7,'ac'=>4),
		'summary'=>array('c'=>524288,'ac'=>4),
		'sup'=>array('c'=>7,'ac'=>4),
		'table'=>array('c'=>4194307,'ac'=>64,'cp'=>array('p')),
		'tbody'=>array('c'=>64,'ac'=>536870912,'cp'=>array('tbody','tfoot','thead')),
		'td'=>array('c'=>33024,'ac'=>1,'cp'=>array('td','th')),
		'textarea'=>array('c'=>15),
		'tfoot'=>array('c'=>64,'ac'=>536870912,'cp'=>array('tbody','thead')),
		'th'=>array('c'=>32768,'ac'=>1,'dd'=>16912,'cp'=>array('td','th')),
		'thead'=>array('c'=>64,'ac'=>536870912),
		'time'=>array('c'=>7,'ac'=>4),
		'tr'=>array('c'=>536870976,'ac'=>32768,'cp'=>array('tr')),
		'track'=>array('c'=>8192,'c13'=>'@src'),
		'u'=>array('c'=>7,'ac'=>4),
		'ul'=>array('c'=>3,'ac'=>1073741824,'cp'=>array('p')),
		'var'=>array('c'=>7,'ac'=>4),
		'video'=>array('c'=>47,'c3'=>'@controls','ac'=>8192,'ac13'=>'@src','t'=>1),
		'wbr'=>array('c'=>5)
	);

	/**
	* Add rules generated from the HTML5 specs
	*
	* @param array $options Array of option settings, see generateRulesFromHTML5Specs()
	*/
	public function addRulesFromHTML5Specs(array $options = array())
	{
		foreach ($this->generateRulesFromHTML5Specs($options) as $tagName => $tagOptions)
		{
			$this->setTagOptions($tagName, $tagOptions);
		}
	}

	/**
	* Generate rules based on HTML5 content models
	*
	* We use the HTML5 specs to determine which children or descendants should be allowed or denied
	* based on HTML5 content models. While it does not exactly match HTML5 content models, it gets
	* pretty close. We also use HTML5 "optional end tag" rules to create closeParent rules.
	*
	* Currently, this method does not evaluate elements created with <xsl:element> correctly, or
	* attributes created with <xsl:attribute> and may never will due to the increased complexity it
	* would entail. Additionally, it does not evaluate the scope of <xsl:apply-templates/>. For
	* instance, it will treat <xsl:apply-templates select="LI"/> as if it was <xsl:apply-templates/>
	*
	* @link http://dev.w3.org/html5/spec/content-models.html#content-models
	* @link http://dev.w3.org/html5/spec/syntax.html#optional-tags
	* @see  ../scripts/patchConfigBuilder.php
	*
	* Possible options:
	*
	*  rootElement: name of the HTML element used as the root of the rendered text
	*
	* @param array $options Array of option settings
	* @return array
	*/
	public function generateRulesFromHTML5Specs(array $options = array())
	{
		$tagsConfig = $this->tags;

		if (isset($options['rootElement']))
		{
			if (!isset($this->htmlElements[$options['rootElement']]))
			{
				throw new InvalidArgumentException("Unknown HTML element '" . $options['rootElement'] . "'");
			}

			/**
			* Create a fake tag for our root element. "*fake-root*" is not a valid tag name so it
			* shouldn't conflict with any existing tag
			*/
			$rootTag = '*fake-root*';

			$tagsConfig[$rootTag]['xsl'] =
				'<xsl:template match="' . $rootTag . '">
					<' . $options['rootElement'] . '>
						<xsl:apply-templates />
					</' . $options['rootElement'] . '>
				</xsl:template>';
		}

		$tagsInfo = array();
		foreach ($tagsConfig as $tagName => $tagConfig)
		{
			/**
			* If a tag has no template set, we try to render it alone and use the result as its
			* pseudo-template
			*/
			if (!isset($tagConfig['xsl']))
			{
				if (!isset($renderer))
				{
					$renderer = $this->getRenderer();
				}

				$uid = uniqid('', true);
				$xml = '<rt' . $this->generateNamespaceDeclarations() . '>'
				     . '<' . $tagName . '>' . $uid . '</' . $tagName . '>'
				     . '</rt>';

				$tagConfig['xsl'] = '<xsl:template match="' . $tagName . '">'
				                  . str_replace($uid, '<xsl:apply-templates/>', $renderer->render($xml))
				                  . '</xsl:template>';
			}

			$tagInfo = array(
				'lastChildren' => array()
			);

			$tagInfo['root'] = simplexml_load_string(
				'<xsl:stylesheet' . $this->generateNamespaceDeclarations() . '>' . $tagConfig['xsl'] . '</xsl:stylesheet>'
			);

			/**
			* Get every HTML element with no HTML ancestor
			*/
			$tagInfo['firstChildren'] = $tagInfo['root']->xpath('//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"][not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]');

			/**
			* Compute the category bitfield of every first element
			*/
			$tagInfo['firstChildrenCategoryBitfield'] = array();

			foreach ($tagInfo['firstChildren'] as $firstChild)
			{
				$tagInfo['firstChildrenCategoryBitfield'][]
					= $this->filterHTMLRulesBitfield($firstChild->getName(), 'c', $firstChild);
			}

			/**
			* Get every HTML element from this tag's template(s) and generate a bitfield that
			* represents all the content models in use
			*/
			$tagInfo['usedCategories'] = 0;

			foreach ($tagInfo['root']->xpath('//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]') as $node)
			{
				$tagInfo['usedCategories'] |= $this->filterHTMLRulesBitfield($node->getName(), 'c', $node);
			}

			/**
			* For each <xsl:apply-templates/> element, iterate over all the HTML ancestors, compute
			* the allowChildBitfields and denyDescendantBitfield values, and save the last HTML
			* child of the branch
			*/
			$tagInfo['denyDescendantBitfield'] = 0;

			foreach ($tagInfo['root']->xpath('//xsl:apply-templates') as $at)
			{
				$allowChildBitfield = null;

				foreach ($at->xpath('ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]') as $node)
				{
					$elName = $node->getName();

					if (empty($this->htmlElements[$elName]['t']))
					{
						/**
						* If this element does not use the transparent content model, we discard its
						* parent's bitfield
						*/
						$allowChildBitfield = 0;

						$tagInfo['isTransparent'] = false;
					}
					elseif (!isset($allowChildBitfield))
					{
						/**
						* If this element uses the transparent content model and this is the first
						* HTML element of this template, we reuse its category bitfield. It's not
						* exactly how it should work though, as at this point we don't know what
						* category enabled this tag
						*/
						$allowChildBitfield
							= $this->filterHTMLRulesBitfield($elName, 'c', $node);

						/**
						* Accumulate the denied descendants
						*/
						$tagInfo['denyDescendantBitfield'] |= $this->filterHTMLRulesBitfield($elName, 'dd', $node);

						if (!isset($tagInfo['isTransparent']))
						{
							$tagInfo['isTransparent'] = true;
						}
					}

					$allowChildBitfield
						|= $this->filterHTMLRulesBitfield($elName, 'ac', $node);
				}

				$tagInfo['allowChildBitfields'][] = $allowChildBitfield;
				$tagInfo['lastChildren'][] = $node;
			}

			$tagsInfo[$tagName] = $tagInfo;
		}

		$tagsOptions = array();

		/**
		* Generate closeParent rules
		*/
		foreach ($tagsInfo as $tagName => $tagInfo)
		{
			if (!empty($tagInfo['isTransparent']))
			{
				$tagsOptions[$tagName]['isTransparent'] = true;
			}

			foreach ($tagInfo['firstChildren'] as $firstChild)
			{
				$elName = $firstChild->getName();

				if (!isset($this->htmlElements[$elName]['cp']))
				{
					continue;
				}

				foreach ($tagsInfo as $targetName => $targetInfo)
				{
					foreach ($targetInfo['lastChildren'] as $lastChild)
					{
						if (in_array($lastChild->getName(), $this->htmlElements[$elName]['cp'], true))
						{
							$tagsOptions[$tagName]['rules']['closeParent'][] = $targetName;
						}
					}
				}
			}
		}

		/**
		* Generate allowChild/denyChild rules
		*/
		foreach ($tagsInfo as $tagName => $tagInfo)
		{
			/**
			* If this tag allows no children, we deny every one of them
			*/
			if (empty($tagInfo['allowChildBitfields']))
			{
				foreach ($tagsInfo as $targetName => $targetInfo)
				{
					$tagsOptions[$tagName]['rules']['denyChild'][] = $targetName;
				}

				continue;
			}

			foreach ($tagInfo['allowChildBitfields'] as $allowChildBitfield)
			{
				foreach ($tagsInfo as $targetName => $targetInfo)
				{
					foreach ($targetInfo['firstChildrenCategoryBitfield'] as $firstChildBitfield)
					{
						$action = ($allowChildBitfield & $firstChildBitfield)
								? 'allowChild'
								: 'denyChild';

						$tagsOptions[$tagName]['rules'][$action][] = $targetName;
					}
				}
			}
		}

		/**
		* Generate denyDescendant rules
		*/
		foreach ($tagsInfo as $tagName => $tagInfo)
		{
			foreach ($tagsInfo as $targetName => $targetInfo)
			{
				if ($tagInfo['denyDescendantBitfield'] & $targetInfo['usedCategories'])
				{
					$tagsOptions[$tagName]['rules']['denyDescendant'][] = $targetName;
				}
			}
		}

		/**
		* Sets the options related to the root element
		*/
		if (isset($options['rootElement']))
		{
			/**
			* Tags that cannot be a child of our root tag gets the disallowAsRoot option
			*/
			if (isset($tagsOptions[$rootTag]['rules']['denyChild']))
			{
				foreach ($tagsOptions[$rootTag]['rules']['denyChild'] as $tagName)
				{
					$tagsOptions[$tagName]['disallowAsRoot'] = true;
				}
			}

			/**
			* Tags that cannot be a descendant of our root tag get the disable option
			*/
			if (isset($tagsOptions[$rootTag]['rules']['denyDescendant']))
			{
				foreach ($tagsOptions[$rootTag]['rules']['denyDescendant'] as $tagName)
				{
					$tagsOptions[$tagName]['disable'] = true;
				}
			}

			/**
			* Now remove any mention of our root tag from the return array
			*/
			unset($tagsOptions[$rootTag]);

			foreach ($tagsOptions as &$tagOptions)
			{
				if (isset($tagOptions['rules']))
				{
					foreach ($tagOptions['rules'] as $rule => $targets)
					{
						/**
						* First we flip the target so we can unset the fake tag by key, then we
						* flip them back, which rearranges their keys as a side-effect
						*/
						$targets = array_flip($targets);
						unset($targets[$rootTag]);
						$tagOptions['rules'][$rule] = array_flip($targets);
					}
				}
			}
			unset($tagOptions);
		}

		/**
		* Deduplicate rules and resolve conflicting rules
		*/
		$precedence = array(
			array('denyDescendant', 'denyChild'),
			array('denyDescendant', 'allowChild'),
			array('denyChild', 'allowChild')
		);

		foreach ($tagsOptions as $tagName => &$tagOptions)
		{
			// flip the rules targets
			$tagOptions['rules'] = array_map('array_flip', $tagOptions['rules']);

			// apply precedence, e.g. if there's a denyChild rule, remove any allowChild rules
			foreach ($precedence as $pair)
			{
				list($k1, $k2) = $pair;

				if (!isset($tagOptions['rules'][$k1], $tagOptions['rules'][$k2]))
				{
					continue;
				}

				$tagOptions['rules'][$k2] = array_diff_key(
					$tagOptions['rules'][$k2],
					$tagOptions['rules'][$k1]
				);
			}

			// flip the rules again
			$tagOptions['rules'] = array_map('array_keys', $tagOptions['rules']);

			// remove empty rules
			$tagOptions['rules'] = array_filter($tagOptions['rules']);
		}
		unset($tagOptions);

		return $tagsOptions;
	}

	/**
	* Filter a bitfield according to its context node
	*
	* @param  string           $elName Name of the HTML element
	* @param  string           $k      Bitfield name: either 'c', 'ac' or 'dd'
	* @param  SimpleXMLElement $node   Context node
	* @return integer
	*/
	protected function filterHTMLRulesBitfield($elName, $k, SimpleXMLElement $node)
	{
		if (empty($this->htmlElements[$elName][$k]))
		{
			return 0;
		}

		$bitfield = $this->htmlElements[$elName][$k];

		foreach (str_split(strrev(decbin($bitfield)), 1) as $n => $v)
		{
			if (!$v)
			{
				continue;
			}

			if (isset($this->htmlElements[$elName][$k . $n])
			 && !$node->xpath($this->htmlElements[$elName][$k . $n]))
			{
				$bitfield ^= 1 << $n;
			}
		}

		return $bitfield;
	}
}