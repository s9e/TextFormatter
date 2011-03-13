<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use InvalidArgumentException,
    RuntimeException,
    UnexpectedValueException;

class ConfigBuilder
{
	protected $tags = array();

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
	// Tag stuff
	//==========================================================================

	/**
	* Define a new tag
	*
	* @param string $tagName    Name of the tag {@see isValidId()}
	* @param array  $tagOptions Tag options (automatically augmented by $this->defaultTagOptions)
	*/
	public function addTag($tagName, array $tagOptions = array())
	{
		if (!ConfigBuilder::isValidId($tagName))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $tagName . "'");
		}

		$tagName = strtoupper($tagName);

		if (isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Tag '" . $tagName . "' already exists");
		}

		$attributes = array();
		if (isset($tagOptions['attributes']))
		{
			$attributes = $tagOptions['attributes'];
			unset($tagOptions['attributes']);
		}

		$rules = array();
		if (isset($tagOptions['rules']))
		{
			$rules = $tagOptions['rules'];
			unset($tagOptions['rules']);
		}

		foreach ($this->defaultTagOptions as $k => $v)
		{
			if (isset($tagOptions[$k]))
			{
				/**
				* Preserve the PHP type of that option
				*/
				settype($v, gettype($tagOptions[$k]));
			}
			else
			{
				$tagOptions[$k] = $v;
			}
		}

		$this->tags[$tagName] = $tagOptions;

		foreach ($attributes as $attrName => $attrConf)
		{
			$this->addAttribute($tagName, $attrName, $attrConf['type'], $attrConf);
		}

		foreach ($rules as $action => $targets)
		{
			foreach ($targets as $target)
			{
				$this->addRule($tagName, $action, $target);
			}
		}
	}

	/**
	* Define an attribute for a tag
	*
	* @param string $tagName
	* @param string $attrName
	* @param string $attrType
	* @param array  $conf
	*/
	public function addAttribute($tagName, $attrName, $attrType, array $attrConf = array())
	{
		if (!ConfigBuilder::isValidId($tagName))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $tagName . "'");
		}

		if (!ConfigBuilder::isValidId($attrName))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $attrName . "'");
		}

		$tagName  = strtoupper($tagName);
		$attrName = strtolower($attrName);

		if (!isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException("Unknown tag '" . $tagName . "'");
		}

		if (isset($this->tags[$tagName]['attributes'][$attrName]))
		{
			throw new InvalidArgumentException("Attribute '" . $attrName . "' already exists");
		}

		/**
		* Add default config
		*/
		$attrConf += array(
			'isRequired' => true
		);

		$attrConf['type'] = $attrType;
		$this->tags[$tagName]['attributes'][$attrName] = $attrConf;
	}

	/**
	* Define a rule
	*
	* @param string $tagName
	* @param string $action
	* @param string $target
	*/
	public function addRule($tagName, $action, $target)
	{
		if (!ConfigBuilder::isValidId($tagName))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $tagName . "'");
		}

		if (!ConfigBuilder::isValidId($target))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $target . "'");
		}

		$tagName = strtoupper($tagName);
		$target  = strtoupper($target);

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
			if (isset($this->rules[$tagName]['requireParent'])
			 && $this->rules[$tagName]['requireParent'] !== $target)
			{
				throw new RuntimeException("Tag '" . $tagName . "' already has a different 'requireParent' rule, and both cannot be satisfied at the same time. Perhaps did you mean to create a 'requireAscendant' rule?");
			}
			$this->tags['rules'][$tagName]['requireParent'] = $target;
		}
		else
		{
			$this->tags['rules'][$tagName][$action][] = $target;
		}
	}

	//==========================================================================
	// Plugins
	//==========================================================================

	public function __get($pluginName)
	{
		return $this->loadPlugin($pluginName);
	}

	/**
	* Load a plugin
	*
	* If a plugin of the same name exists, it will be overwritten. This method knows how to load
	*
	* @param string $pluginName    Name of the plugin
	* @param string $className     Name of the plugin's config class (required for custom plugins)
	* @param string $classFilepath Path to the file that contains the plugin (required if the class
	*                              is not automatically loaded)
	* @param PluginConfig
	*/
	public function loadPlugin($pluginName, $className = null, $classFilepath = null)
	{
		if (!preg_match('#^[A-Z][a-z_0-9]+$#D', $pluginName))
		{
			throw new InvalidArgumentException('Invalid plugin name "' . $pluginName . '"');
		}

		if (!isset($className))
		{
			$className = __NAMESPACE__ . '\\Plugins\\' . $pluginName . 'Config';
			$classFilepath = __DIR__ . '/Plugins/' . $pluginName . 'Config.php';
		}

		if (!class_exists($className)
		 && isset($classFilepath))
		{
			include $classFilepath;
		}

		return $this->$pluginName = new $className($this);
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
		if (!class_exists('Parser'))
		{
			include_once(__DIR__ . '/Parser.php');
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
		if (!class_exists('Renderer'))
		{
			include_once(__DIR__ . '/Renderer.php');
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
		return array(
			'tags'    => $this->getTags(),
			'plugins' => $this->getPluginsConfig(),
			'filters' => $this->getFiltersConfig()
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

		foreach (get_object_vars($this) as $k => $v)
		{
			if ($v instanceof PluginConfig)
			{
				$config[$k] = $v->getConfig();
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
			= '#' . self::buildRegexpFromList($filters['url']['allowedSchemes']) . '$#ADi';

		if (isset($filters['url']['disallowedHosts']))
		{
			$filters['url']['disallowedHosts']
				= '#(?<![^\\.])'
				. self::buildRegexpFromList(
					$filters['url']['disallowedHosts'],
					array('*' => '.*?')
				  )
				. '#DiS';
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
			$allow = array();

			if (!empty($tag['rules']))
			{
				/**
				* Sort the rules so that "deny" overwrites "allow"
				*/
				ksort($tag['rules']);

				foreach ($tag['rules'] as $action => $targets)
				{
					switch ($action)
					{
						case 'allow':
							foreach ($targets as $target)
							{
								$allow[$target] = true;
							}
							break;

						case 'deny':
							foreach ($targets as $target)
							{
								$allow[$target] = false;
							}
							break;

						case 'requireParent':
							/**
							* $targets will be a string here, as we allow only one requireParent
							* rule per tag
							*/
							$tag['requireParent'] = $targets;
							break;

						case 'requireAscendant':
						case 'closeParent':
						default:
							$bbcode[$action] = array_unique($targets);
							sort($bbcode[$action]);
					}
				}
			}

			if ($tag['defaultRule'] === 'allow')
			{
				$allow += array_fill_keys(array_keys($tags), true);
			}

			/**
			* Keep only the tags that are allowed
			*/
			$tag['allow'] = array_filter($allow);
			sort($tag['allow']);

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

	static public function buildRegexpFromList($words, array $esc = array())
	{
		$arr = array();

		foreach ($words as $str)
		{
			if (preg_match_all('#.#us', $str, $matches))
			{
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

		$regexp = self::buildRegexpFromTrie($arr);

		// replace (?:x)? with x?
		$regexp = preg_replace('#\\(\\?:(.)\\)\\?#us', '$1?', $regexp);

		// replace (?:x|y) with [xy]
		$regexp = preg_replace_callback(
			/**
			* Here, we only try to match single letters and numbers because trying to match escaped
			* characters is much more complicated and increases the potential of letting a bug slip
			* by unnoticed, without much gain in return. Just letters, numbers and the underscore is
			* simply safer. Also, we only match low ASCII because we don't know whether the final
			* regexp will be run in Unicode mode.
			*/
			'#\\(\\?:([A-Z_0-9](?:\\|[A-Z_0-9])*)\\)#',
			function($m)
			{
				return '[' . preg_quote(str_replace('|', '', $m[1]), '#') . ']';
			},
			$regexp
		);

		return $regexp;
	}

	static protected function buildRegexpFromTrie($arr)
	{
		if (isset($arr['.*?'])
		 && $arr['.*?'] === array('' => false))
		{
			return '.*?';
		}

		$regexp = '';
		$suffix = '';
		$cnt    = 0;

		if (isset($arr['']))
		{
			unset($arr['']);

			if (empty($arr))
			{
				return '';
			}

			$suffix = '?';
			++$cnt;
		}

		$sep = '';
		foreach ($arr as $c => $sub)
		{
			$regexp .= $sep . $c . self::buildRegexpFromTrie($sub);
			$sep = '|';

			++$cnt;
		}

		if ($cnt > 1)
		{
			return '(?:' . $regexp . ')' . $suffix;
		}

		return $regexp . $suffix;
	}

	static public function isValidId($id)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $id);
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

	public function setBBCodeTemplate($tagName, $tpl, $flags = 0)
	{
		$tagName = $this->normalizeBBCodeId($tagName);
		if (!isset($this->bbcodes[$tagName]))
		{
			throw new InvalidArgumentException("Unknown BBCode '" . $tagName . "'");
		}

		if (!($flags & self::PRESERVE_WHITESPACE))
		{
			// Remove whitespace containing newlines from the template
			$tpl = trim(preg_replace('#>\\s*\\n\\s*<#', '><', $tpl));
		}

		$tpl = '<xsl:template match="' . $tagName . '">'
		     . $tpl
		     . '</xsl:template>';

		$xsl = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $tpl
		     . '</xsl:stylesheet>';

		$old = libxml_use_internal_errors(true);
		$dom = new DOMDocument;
		$res = $dom->loadXML($xsl);
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new InvalidArgumentException('Invalid XML - error was: ' . $error->message);
		}

		if (!($flags & self::ALLOW_INSECURE_TEMPLATES))
		{
			$xpath = new DOMXPath($dom);

			if ($xpath->evaluate('count(//script[contains(@src, "{") or .//xsl:value-of or xsl:attribute])'))
			{
				throw new RuntimeException('It seems that your template contains a <script> tag that uses user-supplied information. Those can be insecure and are disabled by default. Please pass ' . __CLASS__ . '::ALLOW_INSECURE_TEMPLATES as a third parameter to setBBCodeTemplate() to enable it');
			}
		}

		$this->bbcodes[$tagName]['xsl'] = $tpl;
	}
}