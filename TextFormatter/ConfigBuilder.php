<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
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
		$tagName = $this->normalizeTagName($tagName);

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
		return isset($this->tags[$this->normalizeTagName($tagName)]);
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
	* @param  string $tagName Original tag name
	* @return string          Normalized tag name, in uppercase
	*/
	protected function normalizeTagName($tagName)
	{
		if (!static::isValidTagName($tagName))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $tagName . "'");
		}

		return strtoupper($tagName);
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

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
		}

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

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
		}

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

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
		}

		if (isset($this->tags[$tagName]['attrs'][$attrName]))
		{
			throw new InvalidArgumentException("Attribute '" . $attrName . "' already exists");
		}

		/**
		* Add default config
		*/
		$attrConf += array(
			'isRequired' => true
		);

		/**
		* Set attribute type
		*/
		$attrConf['type'] = $attrType;

		$this->tags[$tagName]['attrs'][$attrName] = $attrConf;
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
		$attrName = $this->normalizeAttributeName($attrName);
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
	* @return string           Normalized attribute name, in lowercase
	*/
	protected function normalizeAttributeName($attrName)
	{
		if (!static::isValidAttributeName($attrName))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $attrName . "'");
		}

		return strtolower($attrName);
	}

	//==========================================================================
	// Rule-related methods
	//==========================================================================

	/**
	* Define a rule
	*
	* @param string $tagName
	* @param string $action
	* @param string $target
	*/
	public function addTagRule($tagName, $action, $target)
	{
		$tagName = $this->normalizeTagName($tagName);
		$target  = $this->normalizeTagName($target);

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
		}

		if (!isset($this->tags[$target]))
		{
			throw new InvalidArgumentException("Unknown target tag '" . $target . "'");
		}

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

		if ($action === 'requireParent')
		{
			if (isset($this->tags[$tagName]['rules']['requireParent'])
			 && $this->tags[$tagName]['rules']['requireParent'] !== $target)
			{
				throw new RuntimeException("Tag '" . $tagName . "' already has a different 'requireParent' rule, and both cannot be satisfied at the same time. Perhaps did you mean to create a 'requireAscendant' rule?");
			}
			$this->tags[$tagName]['rules']['requireParent'] = $target;
		}
		else
		{
			$this->tags[$tagName]['rules'][$action][$target] = $target;
		}
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

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
		}

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

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
		}
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
				throw new RuntimeException('It seems that your template contains a <script> tag that uses user-supplied information. Those can be insecure and are disabled by default. Please pass ' . __CLASS__ . '::ALLOW_INSECURE_TEMPLATES as a third parameter to setTagTemplate() to enable it');
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

		$this->tags[$tagName]['xsl'] = $xsl;
	}

	//==========================================================================
	// Plugins
	//==========================================================================

	/**
	* Magic __get automatically loads plugins
	*/
	public function __get($pluginName)
	{
		return $this->loadPlugin($pluginName);
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

			include $classFilepath;
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
	* Set the filter used to validate a param type
	*
	* @param string   $type     Param type
	* @param callback $callback Callback
	* @param array    $conf     Optional config, will be appended to the param config and passed
	*                           to the callback
	*/
	public function setFilter($type, $callback, array $config = array())
	{
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('The second argument passed to ' . __METHOD__ . ' is expected to be a valid callback');
		}

		$this->filters[$type] = array(
			'callback' => $callback
		);

		if (!empty($config))
		{
			$this->filters[$type]['config'] = $config;
		}
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
							* $targets will be a string here, as we allow only one requireParent
							* rule per tag
							*/
							$target = $targets;
							if (!isset($tagsConfig[$target]))
							{
								unset($tag['rules']['requireParent']);
							}
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

				/**
				* Add some default config if missing
				*/
				foreach (array('limit', 'limitAction') as $k)
				{
					if (!isset($pluginConfig[$k]))
					{
						$pluginConfig[$k] = $plugin->$k;
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

	/**
	* Return the complete config, with its XSL stylesheet, in JSON
	*
	* @return array
	*/
	public function getJavascriptParserConfig()
	{
		$config = $this->getParserConfig();

		$config['xsl'] = $this->getXSL();

		return json_encode($config);
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
				$regexp .= '(?=[' . implode('', array_keys($initials)) . '])';
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

	public function addXSL($xsl)
	{
		$xml = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $xsl
		     . '</xsl:stylesheet>';

		$dom = new DOMDocument;

		$old = libxml_use_internal_errors(true);
		$res = $dom->loadXML($xml);
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Malformed XSL - error was: ' . $error->message);
		}

		$this->xsl .= $xsl;
	}
}