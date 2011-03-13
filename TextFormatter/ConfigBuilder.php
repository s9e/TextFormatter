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
	protected $passes = array(
		'BBCode' => array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		),
		'Autolink' => array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		),
		'Censor' => array(
			'limit'               => 1000,
			'limit_action'        => 'warn',
			'default_replacement' => '****'
		),
		'Emoticon' => array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		)
	);


	protected $filters = array(
		'url' => array(
			'allowed_schemes' => array('http', 'https')
		)
	);

	/**
	* @var string Extra XSL to append to the stylesheet
	*/
	protected $xsl = '';

	public $defaultTagOptions = array(
		'tag_limit'     => 100,
		'nesting_limit' => 10,
		'default_rule'  => 'allow'
	);

	public function addTag($tagName, array $tagOptions = array())
	{
		$tagName = strtoupper($tagName);

		if (!ConfigBuilder::isValidId($tagName))
		{
			throw new InvalidArgumentException ("Invalid tag name '" . $tagName . "'");
		}

		if (isset($this->tags[$tagName]))
		{
			throw new InvalidArgumentException('Tag ' . $tagName . ' already exists');
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
	}

	//==========================================================================
	// Passes
	//==========================================================================

	public function addPass($name, array $options)
	{
		if (isset($this->passes[$name]))
		{
			throw new InvalidArgumentException('There is already a pass named ' . $name);
		}

		if (!isset($options['parser']))
		{
			throw new InvalidArgumentException('You must specify a parser for pass ' . $name);
		}

		if (!is_callable($options['parser']))
		{
			throw new InvalidArgumentException('The parser for pass ' . $name . ' must be a valid callback');
		}

		$this->passes[$name] = $options + array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		);
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
	public function setFilter($type, $callback, array $conf = null)
	{
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('The second argument passed to ' . __METHOD__ . ' is expected to be a valid callback');
		}

		$this->filters[$type] = array(
			'callback' => $callback
		);

		if (isset($conf))
		{
			$this->filters[$type]['conf'] = $conf;
		}
	}

	public function allowScheme($scheme)
	{
		$this->filters['url']['allowed_schemes'][] = $scheme;
	}

	public function disallowHost($host)
	{
		/**
		* Transform "*.tld" and ".tld" into the functionally equivalent "tld"
		*
		* As a side-effect, when someone bans *.example.com it also bans example.com (no subdomain)
		* but that's usually what people were trying to achieve.
		*/
		$this->filters['url']['disallowed_hosts'][] = ltrim($host, '*.');
	}

	public function getFiltersConfig()
	{
		$filters = $this->filters;

		$filters['url']['allowed_schemes']
			= '#' . self::buildRegexpFromList($filters['url']['allowed_schemes']) . '$#ADi';

		if (isset($filters['url']['disallowed_hosts']))
		{
			$filters['url']['disallowed_hosts']
				= '#(?<![^\\.])'
				. self::buildRegexpFromList(
					$filters['url']['disallowed_hosts'],
					array('*' => '.*?')
				  )
				. '#DiS';
		}

		return $filters;
	}

	//==========================================================================
	// Misc
	//==========================================================================

	public function setOption($pass, $k, $v)
	{
		if ($k === 'bbcode' || $k === 'param')
		{
			if (!self::isValidId($v))
			{
				throw new InvalidArgumentException ("Invalid $k name '" . $v . "'");
			}

			if ($k === 'bbcode')
			{
				$v = $this->normalizeBBCodeId($v);

				if (!isset($this->bbcodes[$v]))
				{
					trigger_error('Unknown BBCode ' . $v, E_USER_NOTICE);
					// @codeCoverageIgnoreStart
				}
				// @codeCoverageIgnoreEnd
			}
			else
			{
				$v = strtolower($v);

				if (isset($this->passes[$pass]['bbcode']))
				{
					$bbcode = $this->passes[$pass]['bbcode'];

					if (!isset($this->bbcodes[$bbcode]['params'][$v]))
					{
						trigger_error('Unknown BBCode param ' . $v, E_USER_NOTICE);
						// @codeCoverageIgnoreStart
					}
					// @codeCoverageIgnoreEnd
				}
			}
		}
		$this->passes[$pass][$k] = $v;
	}

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

	public function getParser()
	{
		if (!class_exists('Parser'))
		{
			include_once(__DIR__ . '/Parser.php');
		}
		return new Parser($this->getParserConfig());
	}

	public function getRenderer()
	{
		if (!class_exists('Renderer'))
		{
			include_once(__DIR__ . '/Renderer.php');
		}
		return new Renderer($this->getXSL());
	}

	public function getParserConfig()
	{
		$passes = array('BBCode' => null);

		foreach ($this->passes as $pass => $config)
		{
			if ($pass === 'BBCode')
			{
				// do it later
				continue;
			}

			$method = 'get' . $pass . 'Config';

			if (method_exists($this, $method))
			{
				/**
				* Finalize the config
				*/
				$config = $this->$method();

				if ($config === false)
				{
					continue;
				}
			}

			$passes[$pass] = $config;
		}

		$passes['BBCode'] = $this->getBBCodeConfig();

		return array(
			'passes'  => $passes,
			'filters' => $this->getFiltersConfig()
		);
	}

	public function getJavascriptParserConfig()
	{
		$config = $this->getParserConfig();

		$config['xsl'] = $this->getXSL();

		return json_encode($config);
	}

	static public function isValidId($id)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $id);
	}

	public function getXSL()
	{
		/**
		* Force the automatic creation of default BBCodes for Autolink/Censor/Emoticons
		*/
		$this->getParserConfig();

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

		foreach ($this->bbcodes as $bbcode)
		{
			if (isset($bbcode['tpl']))
			{
				$xsl .= $bbcode['tpl'];
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