<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use DOMDocument,
	DOMXPath,
	InvalidArgumentException,
    RuntimeException,
    UnexpectedValueException;

class ConfigBuilder
{
	/**
	* Allow user-supplied data to be used in sensitive area of a template
	* @see self::setTagTemplate()
	*/
	const ALLOW_INSECURE_TEMPLATES = 1;

	/**
	* Whether or not preserve redundant whitespace in a template
	* @see  self::setTagTemplate()
	* @link http://www.php.net/manual/en/class.domdocument.php#domdocument.props.preservewhitespace
	*/
	const PRESERVE_WHITESPACE      = 2;

	/**
	* @var array Tags repository
	*/
	protected $tags = array();

	/**
	* @var array Holds filters' configuration
	*/
	protected $filters = array(
		'url' => array(
			'allowedSchemes' => array('http', 'https')
		)
	);

	/**
	* @var string Extra XSL to append to the stylesheet
	*/
	protected $xsl = '';

	/**
	* @var array  Default options applied to tags, can be overriden by options passed by plugins
	*/
	public $defaultTagOptions = array(
		'tagLimit'     => 100,
		'nestingLimit' => 10,
		'defaultRule'  => 'allow'
	);

	//==========================================================================
	// Tag-related methods
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
		* Create the tag with the default options
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
	static public function isValidTagName($tagName)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $tagName);
	}

	/**
	* Validate and normalize a tag name
	*
	* @param  string $tagName   Original tag name
	* @param  bool   $mustExist If TRUE, throw an exception if the tag does not exist
	* @return string            Normalized tag name, in uppercase
	*/
	protected function normalizeTagName($tagName, $mustExist = true)
	{
		if (!static::isValidTagName($tagName))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $tagName . "'");
		}

		$tagName = strtoupper($tagName);

		if ($mustExist && !isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
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
	* @return array
	*/
	public function getTagOption($tagName, $optionName)
	{
		$tagName = $this->normalizeTagName($tagName);

		if (!isset($this->tags[$tagName][$optionName]))
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
					$this->addTagAttribute($tagName, $attrName, $attrConf['type'], $attrConf);
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

			case 'preFilter':
			case 'postFilter':
				$this->clearTagCallbacks($optionName, $tagName);
				foreach ($optionValue as $callbackConf)
				{
					// add the default params config if it's not set
					$callbackConf += array('params' => array('attrs' => null));

					$this->addTagCallback(
						$optionName,
						$tagName,
						$callbackConf['callback'],
						$callbackConf['params']
					);
				}
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

	/**
	* Remove all preFilter callbacks associated with a tag
	*
	* @param string $tagName
	*/
	public function clearTagPreFilterCallbacks($tagName)
	{
		$this->clearTagCallbacks('preFilter', $tagName);
	}

	/**
	* Remove all postFilter callbacks associated with a tag
	*
	* @param string $tagName
	*/
	public function clearTagPostFilterCallbacks($tagName)
	{
		$this->clearTagCallbacks('postFilter', $tagName);
	}

	/**
	* Remove all phase callbacks associated with a tag
	*
	* @param string $phase    Either 'preFilter' or 'postFilter'
	* @param string $tagName
	*/
	protected function clearTagCallbacks($phase, $tagName)
	{
		$tagName = $this->normalizeTagName($tagName);

		unset($this->tags[$tagName][$phase]);
	}

	/**
	* Add a preFilter callback to a tag
	*
	* @param string   $tagName
	* @param callback $callback
	* @param array    $params
	*/
	public function addTagPreFilterCallback($tagName, $callback, array $params = array('attrs' => null))
	{
		$this->addTagCallback('preFilter', $tagName, $callback, $params);
	}

	/**
	* Add a postFilter callback to a tag's attribute
	*
	* @param string   $tagName
	* @param callback $callback
	* @param array    $params
	*/
	public function addTagPostFilterCallback($tagName, $callback, array $params = array('attrs' => null))
	{
		$this->addTagCallback('postFilter', $tagName, $callback, $params);
	}

	/**
	* Add a phase callback to a tag
	*
	* @param string   $phase    Either 'preFilter' or 'postFilter'
	* @param string   $tagName
	* @param callback $callback
	* @param array    $params
	*/
	protected function addTagCallback($phase, $tagName, $callback, array $params)
	{
		$tagName = $this->normalizeTagName($tagName);

		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('Not a callback');
		}

		$this->tags[$tagName][$phase][] = array(
			'callback' => $callback,
			'params'   => $params
		);
	}

	//==========================================================================
	// Attribute-related methods
	//==========================================================================

	/**
	* Define an attribute for a tag
	*
	* @param string $tagName
	* @param string $attrName
	* @param string $attrType
	* @param array  $conf
	*/
	public function addTagAttribute($tagName, $attrName, $attrType, array $attrConf = array())
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		if (isset($this->tags[$tagName]['attrs'][$attrName]))
		{
			throw new InvalidArgumentException("Attribute '" . $attrName . "' already exists");
		}

		/**
		* Set attribute type
		*/
		$attrConf['type'] = $attrType;

		/**
		* Add the attribute with default config values;
		*/
		$this->tags[$tagName]['attrs'][$attrName] = array(
			/**
			* Compound attributes are not required by default. The attributes they split into
			* should be already. Plus, we remove compound attributes during parsing.
			*/
			'isRequired' => (bool) ($attrType !== 'compound')
		);

		$this->setTagAttributeOptions($tagName, $attrName, $attrConf);
	}

	/**
	* Set several options in a tag's attribute config
	*
	* @param string $tagName
	* @param string $attrName
	* @param array  $options
	*/
	public function setTagAttributeOptions($tagName, $attrName, $options)
	{
		foreach ($options as $optionName => $optionValue)
		{
			$this->setTagAttributeOption($tagName, $attrName, $optionName, $optionValue);
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
	public function setTagAttributeOption($tagName, $attrName, $optionName, $optionValue)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName, $tagName);

		$attrConf =& $this->tags[$tagName]['attrs'][$attrName];

		switch ($optionName)
		{
			case 'preFilter':
			case 'postFilter':
				$this->clearTagAttributeCallbacks($optionName, $tagName, $attrName);

				foreach ($optionValue as $callbackConf)
				{
					// add the default params config if it's not set
					$callbackConf += array('params' => array('attrVal' => null));

					$this->addTagAttributeCallback(
						$optionName,
						$tagName,
						$attrName,
						$callbackConf['callback'],
						$callbackConf['params']
					);
				}
				break;

			default:
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
	public function getTagAttributeOptions($tagName, $attrName)
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
	public function getTagAttributeOption($tagName, $attrName, $optionName)
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
	static public function isValidAttributeName($attrName)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $attrName);
	}

	/**
	* Validate and normalize an attribute name
	*
	* @param  string $attrName Original attribute name
	* @param  string $tagName  If set, check that the attribute exists for given tag and throw an
	*                          exception otherwise
	* @return string           Normalized attribute name, in lowercase
	*/
	protected function normalizeAttributeName($attrName, $tagName = null)
	{
		if (!static::isValidAttributeName($attrName))
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
	* Remove all preFilter callbacks associated with an attribute
	*
	* @param string $tagName
	* @param string $attrName
	*/
	public function clearTagAttributePreFilterCallbacks($tagName, $attrName)
	{
		$this->clearTagAttributeCallbacks('preFilter', $tagName, $attrName);
	}

	/**
	* Remove all postFilter callbacks associated with an attribute
	*
	* @param string $tagName
	* @param string $attrName
	*/
	public function clearTagAttributePostFilterCallbacks($tagName, $attrName)
	{
		$this->clearTagAttributeCallbacks('postFilter', $tagName, $attrName);
	}

	/**
	* Remove all phase callbacks associated with an attribute
	*
	* @param string $phase    Either 'preFilter' or 'postFilter'
	* @param string $tagName
	* @param string $attrName
	*/
	protected function clearTagAttributeCallbacks($phase, $tagName, $attrName)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		unset($this->tags[$tagName]['attrs'][$attrName][$phase]);
	}

	/**
	* Add a preFilter callback to a tag's attribute
	*
	* @param string   $tagName
	* @param string   $attrName
	* @param callback $callback
	* @param array    $params
	*/
	public function addTagAttributePreFilterCallback($tagName, $attrName, $callback, array $params = array('attrVal' => null))
	{
		$this->addTagAttributeCallback('preFilter', $tagName, $attrName, $callback, $params);
	}

	/**
	* Add a postFilter callback to a tag's attribute
	*
	* @param string   $tagName
	* @param string   $attrName
	* @param callback $callback
	* @param array    $params
	*/
	public function addTagAttributePostFilterCallback($tagName, $attrName, $callback, array $params = array('attrVal' => null))
	{
		$this->addTagAttributeCallback('postFilter', $tagName, $attrName, $callback, $params);
	}

	/**
	* Add a phase callback to a tag's attribute
	*
	* @param string   $phase    Either 'preFilter' or 'postFilter'
	* @param string   $tagName
	* @param string   $attrName
	* @param callback $callback
	* @param array    $params
	*/
	protected function addTagAttributeCallback($phase, $tagName, $attrName, $callback, array $params)
	{
		$tagName  = $this->normalizeTagName($tagName);
		$attrName = $this->normalizeAttributeName($attrName);

		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('Not a callback');
		}

		$this->tags[$tagName]['attrs'][$attrName][$phase][] = array(
			'callback' => $callback,
			'params'   => $params
		);
	}

	//==========================================================================
	// Rule-related methods
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
			'allow',
			'closeParent',
			'deny',
			'requireParent',
			'requireAscendant'
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
	// Tag template-related methods
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
	* Magic __get automatically loads plugins, PredefinedTags class
	*
	* @param  string $k Property name
	* @return mixed
	*/
	public function __get($k)
	{
		if ($k === 'predefinedTags')
		{
			if (!class_exists(__NAMESPACE__ . '\\PredefinedTags'))
			{
				include __DIR__ . '/PredefinedTags.php';
			}

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
	* If a plugin of the same name exists, it will be overwritten. This method knows how to load
	* core plugins. Otherwise, you have to include the appropriate files beforehand.
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

		$classFilepath = null;

		if (!isset($className))
		{
			$className = __NAMESPACE__ . '\\Plugins\\' . $pluginName . 'Config';
			$classFilepath = __DIR__ . '/Plugins/' . $pluginName . 'Config.php';
		}

		$useAutoload = !isset($classFilepath);

		/**
		* We test whether the class exists. If a filepath was provided, we disable autoload
		*/
		if (!class_exists($className, $useAutoload)
		 && isset($classFilepath))
		{
			/**
			* Load the PluginConfig abstract class if necessary
			*/
			if (!class_exists(__NAMESPACE__ . '\\PluginConfig', $useAutoload))
			{
				include __DIR__ . '/PluginConfig.php';
			}

			if (file_exists($classFilepath))
			{
				include $classFilepath;
			}
		}

		if (!class_exists($className))
		{
			throw new RuntimeException("Class '" . $className . "' not found");
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
		if (!class_exists(__NAMESPACE__ . '\\Parser'))
		{
			include __DIR__ . '/Parser.php';
		}

		return new Parser($this->getParserConfig());
	}

	/**
	* Return an instance of Renderer based on the current config
	*
	* @return Renderer
	*/
	public function getRenderer()
	{
		if (!class_exists(__NAMESPACE__ . '\\Renderer'))
		{
			include __DIR__ . '/Renderer.php';
		}

		return new Renderer($this->getXSL());
	}

	//==========================================================================
	// Filters
	//==========================================================================

	/**
	* Set the filter used to validate an attribute type
	*
	* @param string $filterType Attribute type this filter is in charge of
	* @param array  $filterConf Callback
	*/
	public function setFilter($filterType, array $filterConf)
	{
		if (!isset($filterConf['params']))
		{
			$filterConf['params'] = array();
		}

		if (!is_callable($filterConf['callback']))
		{
			throw new InvalidArgumentException('Not a callback');
		}

		$this->filters[$filterType] = $filterConf;
	}

	/**
	* Allow a URL scheme
	*
	* @param string $scheme URL scheme, e.g. "file" or "ed2k"
	*/
	public function allowScheme($scheme)
	{
		$this->filters['url']['allowedSchemes'][] = $scheme;
	}

	/**
	* Return the list of allowed URL schemes
	*
	* @return array
	*/
	public function getAllowedSchemes()
	{
		return $this->filters['url']['allowedSchemes'];
	}

	/**
	* Disallow a hostname (or hostname mask) from being used in URLs
	*
	* @param string $scheme URL scheme, e.g. "file" or "ed2k"
	*/
	public function disallowHost($host)
	{
		/**
		* Transform "*.tld" and ".tld" into the functionally equivalent "tld"
		*
		* As a side-effect, when someone bans *.example.com it also bans example.com (no subdomain)
		* but that's usually what people were trying to achieve.
		*/
		$this->filters['url']['disallowedHosts'][] = ltrim($host, '*.');
	}

	//==========================================================================
	// Config
	//==========================================================================

	/**
	* Return the config needed by the global parser
	*
	* @return array
	*/
	public function getParserConfig()
	{
		$tagsConfig = $this->getTagsConfig();

		foreach ($tagsConfig as &$tag)
		{
			/**
			* Build the list of allowed descendants and remove everything that's not needed by the
			* global parser
			*/
			$allow = array();

			if (isset($tag['rules']))
			{
				/**
				* Sort the rules so that "deny" overwrites "allow"
				*/
				ksort($tag['rules']);

				foreach ($tag['rules'] as $action => &$targets)
				{
					switch ($action)
					{
						case 'allow':
							foreach ($targets as $target)
							{
								$allow[$target] = true;
							}

							// We don't need those anymore
							unset($tag['rules']['allow']);
							break;

						case 'deny':
							foreach ($targets as $target)
							{
								$allow[$target] = false;
							}

							// We don't need those anymore
							unset($tag['rules']['deny']);
							break;

						case 'requireParent':
							/**
							* Nothing to do here. If the target tag does not exist, this tag will
							* never be valid but we still leave it in the configuration
							*/
							break;

						case 'requireAscendant':
						case 'closeParent':
						default:
							// keep only the rules that target existing tags
							$targets = array_intersect_key($targets, $tagsConfig);

							// This will sort the array and reset the keys
							sort($targets);
					}
				}
				unset($targets);

				/**
				* Remove rules with no targets
				*/
				$tag['rules'] = array_filter($tag['rules']);

				if (empty($tag['rules']))
				{
					unset($tag['rules']);
				}
			}

			if ($tag['defaultRule'] === 'allow')
			{
				$allow += array_fill_keys(array_keys($tagsConfig), true);
			}

			/**
			* Keep only the tags that are allowed
			*/
			$tag['allow'] = array_filter($allow);
			ksort($tag['allow']);

			unset($tag['defaultRule']);
		}
		unset($tag);

		return array(
			'filters' => $this->getFiltersConfig(),
			'plugins' => $this->getPluginsConfig(),
			'tags'    => $tagsConfig
		);
	}

	/**
	* Return the configs generated by plugins
	*
	* @return array
	*/
	public function getPluginsConfig()
	{
		$config = array();

		foreach (get_object_vars($this) as $pluginName => $plugin)
		{
			if ($plugin instanceof PluginConfig)
			{
				$pluginConfig = $plugin->getConfig();

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
		}

		return $config;
	}

	/**
	* Return the list of filters and their config
	*
	* @return array
	*/
	public function getFiltersConfig()
	{
		$filters = $this->filters;

		$filters['url']['allowedSchemes']
			= '#^' . self::buildRegexpFromList($filters['url']['allowedSchemes']) . '$#Di';

		if (isset($filters['url']['disallowedHosts']))
		{
			$filters['url']['disallowedHosts']
				= '#(?<![^\\.])'
				. self::buildRegexpFromList(
					$filters['url']['disallowedHosts'],
					array('*' => '.*')
				  )
				. '$#DiS';
		}

		return $filters;
	}

	/**
	* Return the tags' config, normalized and sorted, minus the tags' templates
	*
	* @return array
	*/
	public function getTagsConfig()
	{
		$tags = $this->tags;
		ksort($tags);

		foreach ($tags as $tagName => &$tag)
		{
			/**
			* We don't need the tag's template
			*/
			unset($tag['xsl']);

			ksort($tag);
		}

		return $tags;
	}

	//==========================================================================
	// Misc tools
	//==========================================================================

	/**
	* Create a regexp pattern that matches a list of words
	*
	* @param  array  $words Words to sort (UTF-8 expected)
	* @param  array  $esc   Array that caches how each individual characters should be escaped
	* @return string
	*/
	static public function buildRegexpFromList($words, array $esc = array())
	{
		// Sort the words to produce the same regexp regardless of the words' order
		sort($words);

		$initials = array();

		$arr = array();
		foreach ($words as $word)
		{
			if (preg_match_all('#.#us', $word, $matches))
			{
				/**
				* Store the initial for later
				*/
				$initials[$matches[0][0]] = true;

				$cur =& $arr;
				foreach ($matches[0] as $c)
				{
					if (!isset($esc[$c]))
					{
						$esc[$c] = preg_quote($c, '#');
					}

					$cur =& $cur[$esc[$c]];
				}
				$cur[''] = false;
			}
		}
		unset($cur);

		$regexp = '';

		/**
		* Test whether none of the initials has a special meaning
		*/
		if (count($initials) > 1)
		{
			$useLookahead = true;
			foreach ($initials as $initial => $void)
			{
				if ($esc[$initial] !== preg_quote($initial, '#'))
				{
					$useLookahead = false;
					break;
				}
			}

			if ($useLookahead)
			{
				$regexp .= '(?=[' . implode('', array_intersect_key($esc, $initials)) . '])';
			}
		}

		$regexp .= self::buildRegexpFromTrie($arr);

		return $regexp;
	}

	static protected function buildRegexpFromTrie($arr)
	{
		foreach (array('.*', '.*?') as $expr)
		{
			if (isset($arr[$expr])
			 && $arr[$expr] === array('' => false))
			{
				return $expr;
			}
		}

		$regexp = '';
		$suffix = '';
		$cnt    = count($arr);

		if (isset($arr['']))
		{
			unset($arr['']);

			if (empty($arr))
			{
				return '';
			}

			$suffix = '?';
		}

		/**
		* See if we can use a character class to produce [xy] instead of (?:x|y)
		*/
		$useCharacterClass = (bool) ($cnt > 1);
		foreach ($arr as $c => $sub)
		{
			/**
			* If this is not the last character, we can't use a character class
			*/
			if ($sub !== array('' => false))
			{
				$useCharacterClass = false;
				break;
			}

			/**
			* If this is a special character, we can't use a character class
			*/
			if ($c !== preg_quote(stripslashes($c), '#'))
			{
				$useCharacterClass = false;
				break;
			}
		}

		if ($useCharacterClass)
		{
			if ($cnt === 2 && $suffix)
			{
				/**
				* Produce x? instead of [x]?
				*/
				return implode('', array_keys($arr)) . $suffix;
			}

			return '[' . implode('', array_keys($arr)) . ']' . $suffix;
		}

		$sep = '';
		foreach ($arr as $c => $sub)
		{
			$regexp .= $sep . $c . self::buildRegexpFromTrie($sub);
			$sep = '|';
		}

		if ($cnt > 1)
		{
			return '(?:' . $regexp . ')' . $suffix;
		}

		return $regexp . $suffix;
	}

	//==========================================================================
	// XSL stuff
	//==========================================================================

	public function getXSL()
	{
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . "\n"
			 . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
			 . '<xsl:output method="html" encoding="utf-8" omit-xml-declaration="yes" indent="no"/>'
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
		      . '<xsl:template match="st"/>'
		      . '<xsl:template match="et"/>'
		      . '<xsl:template match="i"/>'
		      . '</xsl:stylesheet>';

		return $xsl;
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
	* Normalize XSL
	*
	* Check for well-formedness, remove whitespace if applicable.
	* Check for insecure script tags.
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
		$xsl = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
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

		if (!($flags & self::ALLOW_INSECURE_TEMPLATES))
		{
			$xpath = new DOMXPath($dom);

			if ($xpath->evaluate('count(//script[contains(@src, "{") or .//xsl:value-of or .//xsl:attribute])'))
			{
				throw new RuntimeException('It seems that your template contains a <script> tag that uses user-supplied information. Those can be insecure and are disabled by default. Please use the ' . __CLASS__ . '::ALLOW_INSECURE_TEMPLATES flag to enable it');
			}

			if ($xpath->evaluate('count(//*[@disable-output-escaping])'))
			{
				throw new RuntimeException("It seems that your template contains a 'disable-output-escaping' attribute. Those can be insecure and are disabled by default. Please use the " . __CLASS__ . "::ALLOW_INSECURE_TEMPLATES flag to enable it");
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
}