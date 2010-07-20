<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Markup;

class ConfigBuilder
{
	const ALLOW_INSECURE_TEMPLATES = 1;
	const PRESERVE_WHITESPACE      = 2;

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
			'limit'        => 1000,
			'limit_action' => 'warn'
		),
		'Emoticon' => array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		)
	);

	protected $bbcodes = array();
	protected $bbcode_rules = array();
	protected $bbcode_aliases = array();

	protected $censor = array();

	protected $emoticons = array();

	protected $filters = array(
		'url' => array(
			'allowed_schemes' => array('http', 'https')
		)
	);

	/**
	* @var Extra XSL to append to the stylesheet
	*/
	protected $xsl = '';

	//==========================================================================
	// Passes
	//==========================================================================

	public function addPass($name, array $options)
	{
		if (isset($this->passes[$name]))
		{
			throw new \InvalidArgumentException('There is already a pass named ' . $name);
		}

		if (!isset($options['parser']))
		{
			throw new \InvalidArgumentException('You must specify a parser for pass ' . $name);
		}

		if (!is_callable($options['parser']))
		{
			throw new \InvalidArgumentException('The parser for pass ' . $name . ' must be a valid callback');
		}

		$this->passes[$name] = $options + array(
			'limit'        => 1000,
			'limit_action' => 'ignore'
		);
	}

	//==========================================================================
	// Autolink
	//==========================================================================

	public function setAutolinkOption($k, $v)
	{
		$this->setOption('Autolink', $k, $v);
	}

	public function getAutolinkConfig()
	{
		$config = $this->passes['Autolink'];

		if (!isset($config['bbcode'], $config['param']))
		{
			return false;
		}

		$config['regexp'] =
			'#' . self::buildRegexpFromList($this->filters['url']['allowed_schemes']) . '://\\S+#iS';

		return $config;
	}

	//==========================================================================
	// BBCode
	//==========================================================================

	public function setBBCodeOption($k, $v)
	{
		$this->setOption('BBCode', $k, $v);
	}

	public function addBBCode($bbcode_id, array $options = array())
	{
		if (!self::isValidId($bbcode_id))
		{
			throw new \InvalidArgumentException ("Invalid BBCode name '" . $bbcode_id . "'");
		}

		$bbcode_id = $this->normalizeBBCodeId($bbcode_id);

		if (isset($this->bbcodes[$bbcode_id]))
		{
			throw new \InvalidArgumentException('BBCode ' . $bbcode_id . ' already exists');
		}

		$bbcode = $this->getDefaultBBCodeOptions();
		foreach ($options as $k => $v)
		{
			if (isset($bbcode[$k]))
			{
				/**
				* Preserve the PHP type of that option
				*/
				settype($v, gettype($bbcode[$k]));
			}

			$bbcode[$k] = $v;
		}

		$this->bbcodes[$bbcode_id] = $bbcode;
		$this->bbcode_aliases[$bbcode_id] = $bbcode_id;
	}

	public function addBBCodeAlias($bbcode_id, $alias)
	{
		$bbcode_id = $this->normalizeBBCodeId($bbcode_id);
		$alias     = $this->normalizeBBCodeId($alias);

		if (!isset($this->bbcodes[$bbcode_id]))
		{
			throw new \InvalidArgumentException("Unknown BBCode '" . $bbcode_id . "'");
		}
		if (isset($this->bbcodes[$alias]))
		{
			throw new \InvalidArgumentException("Cannot create alias '" . $alias . "' - a BBCode using that name already exists");
		}

		/**
		* For the time being, restrict aliases to a-z, 0-9, _ and * with no restriction on first
		* char
		*/
		if (!preg_match('#^[A-Z_0-9\\*]+$#D', $alias))
		{
			throw new \InvalidArgumentException("Invalid alias name '" . $alias . "'");
		}
		$this->bbcode_aliases[$alias] = $bbcode_id;
	}

	public function addBBCodeParam($bbcode_id, $param_name, $param_type, $is_required = true)
	{
		$bbcode_id = $this->normalizeBBCodeId($bbcode_id);
		if (!isset($this->bbcodes[$bbcode_id]))
		{
			throw new \InvalidArgumentException("Unknown BBCode '" . $bbcode_id . "'");
		}

		if (!self::isValidId($param_name))
		{
			throw new \InvalidArgumentException ("Invalid param name '" . $param_name . "'");
		}

		$param_name = strtolower($param_name);

		if (isset($this->bbcodes[$bbcode_id]['params'][$param_name]))
		{
			throw new \InvalidArgumentException('Param ' . $param_name . ' already exists');
		}

		$this->bbcodes[$bbcode_id]['params'][$param_name] = array(
			'type'			=> $param_type,
			'is_required'	=> $is_required
		);
	}

	public function addBBCodeRule($bbcode_id, $action, $target)
	{
		if (!in_array($action, array(
			'allow',
			'close_parent',
			'deny',
			'require_parent',
			'require_ascendant'
		), true))
		{
			throw new \UnexpectedValueException("Unknown rule action '" . $action . "'");
		}

		$bbcode_id = $this->normalizeBBCodeId($bbcode_id);
		$target    = $this->normalizeBBCodeId($target);

		if ($action === 'require_parent')
		{
			if (isset($this->bbcode_rules[$bbcode_id]['require_parent'])
			 && $this->bbcode_rules[$bbcode_id]['require_parent'] !== $target)
			{
				throw new \RuntimeException("BBCode $bbcode_id already has a require_parent rule");
			}
			$this->bbcode_rules[$bbcode_id]['require_parent'] = $target;
		}
		else
		{
			$this->bbcode_rules[$bbcode_id][$action][] = $target;
		}
	}

	public function getBBCodeConfig()
	{
		$config = $this->passes['BBCode'];
		$config['aliases'] = $this->bbcode_aliases;
		$config['bbcodes'] = $this->bbcodes;
		unset($config['tpl']);

		$bbcode_ids = array_keys($this->bbcodes);

		foreach ($config['bbcodes'] as $bbcode_id => &$bbcode)
		{
			$allow = array();

			if (isset($this->bbcode_rules[$bbcode_id]))
			{
				/**
				* Sort the rules so that "deny" overwrite "allow"
				*/
				ksort($this->bbcode_rules[$bbcode_id]);

				foreach ($this->bbcode_rules[$bbcode_id] as $action => $targets)
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

						case 'require_parent':
							$bbcode['require_parent'] = $targets;
							break;

						default:
							$bbcode[$action] = array_unique($targets);
					}
				}
			}

			if ($bbcode['default_rule'] === 'allow')
			{
				$allow += array_fill_keys($bbcode_ids, true);
			}

			/**
			* Keep only the BBCodes that are allowed
			*/
			$bbcode['allow'] = array_filter($allow);

			if (isset($bbcode['default_param'])
			 && !isset($bbcode['params'][$bbcode['default_param']]))
			{
				trigger_error("Skipping unknown BBCode param '" . $k . "'", E_USER_NOTICE);
			}
		}
		unset($bbcode);

		$aliases = array();
		foreach ($this->bbcode_aliases as $alias => $bbcode_id)
		{
			if (empty($this->bbcodes[$bbcode_id]['internal_use']))
			{
				$aliases[] = $alias;
			}
		}

		$regexp = self::buildRegexpFromList($aliases);
		$config['regexp'] =
			'#\\[/?(' . preg_replace('#^\\(\\?:(.*)\\)$#D', '$1', $regexp) . ')(?=[\\] =:/])#i';

		return $config;
	}

	public function getDefaultBBCodeOptions()
	{
		return array(
			'tag_limit'        => 100,
			'nesting_limit'    => 10,
			'default_rule'     => 'allow',
			'content_as_param' => false
		);
	}

	public function setBBCodeTemplate($bbcode_id, $tpl, $flags = 0)
	{
		$bbcode_id = $this->normalizeBBCodeId($bbcode_id);
		if (!isset($this->bbcodes[$bbcode_id]))
		{
			throw new \InvalidArgumentException("Unknown BBCode '" . $bbcode_id . "'");
		}

		if (!($flags & self::PRESERVE_WHITESPACE))
		{
			// Remove whitespace containing newlines from the template
			$tpl = trim(preg_replace('#>\\s*\\n\\s*<#', '><', $tpl));
		}

		$tpl = '<xsl:template match="' . $bbcode_id . '">'
		     . $tpl
		     . '</xsl:template>';

		$xsl = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $tpl
		     . '</xsl:stylesheet>';

		$old = libxml_use_internal_errors(true);
		$dom = new \DOMDocument;
		$res = $dom->loadXML($xsl);
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new \InvalidArgumentException('Invalid XML - error was: ' . $error->message);
		}

		if (!($flags & self::ALLOW_INSECURE_TEMPLATES))
		{
			$xpath = new \DOMXPath($dom);

			if ($xpath->query('//script[contains(@src, "{") or .//xsl:value-of]')->length)
			{
				throw new \Exception('It seems that your template contains <script> tag using user-supplied information. Those can be insecure and are disabled by default. Please pass ' . __CLASS__ . '::ALLOW_INSECURE_TEMPLATES as a third parameter to setBBCodeTemplate() to enable it');
			}

/*
			foreach ($xpath->query('//@style[contains(., "{")]') as $attr)
			{
				print_r($attr);
				exit;
			}
*/
		}

		/**
		* Strip the whitespace off that template, except in <xsl:text/> elements
		*/
/**
		if (!isset($this->xslt))
		{
			$xsl = new \DOMDocument;
			$xsl->loadXML('<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <xsl:output method="xml" encoding="utf-8" indent="no" />
	<xsl:preserve-space elements="xsl:text" />

    <xsl:template match="*">
        <xsl:copy>
            <xsl:copy-of select="@*" />
            <xsl:apply-templates />
        </xsl:copy>
    </xsl:template>

</xsl:stylesheet>');

			$this->xslt = new \XSLTProcessor;
			$this->xslt->importStylesheet($xsl);
		}

		$dom = $this->xslt->transformToDoc($dom);
		$tpl = $dom->saveXML($dom->documentElement);
		$tpl = substr($tpl, 1 + strpos($tpl, '>'), strrpos($tpl, '<') - strlen($tpl));
/**/

		$this->bbcodes[$bbcode_id]['tpl'] = $tpl;
	}

	public function addBBCodeFromExample($def, $tpl, $flags = 0)
	{
		$regexp = '#'
		        . '\\[([a-zA-Z_][a-zA-Z_0-9]*)(=\\{[A-Z_]+[0-9]*\\})?'
		        . '((?:\\s+[a-zA-Z_][a-zA-Z_0-9]*=\\{[A-Z_]+\\})*)'
		        . '(?:\\s*/\\]|\\](\\{[A-Z_]+[0-9]*\\})?\\[/\\1])'
		        . '$#';

		if (!preg_match($regexp, trim($def), $m))
		{
			throw new \InvalidArgumentException('Cannot interpret the BBCode definition');
		}

		$old = libxml_use_internal_errors(true);
		$dom = new \DOMDocument;
		$res = $dom->loadXML('<t>' . $tpl . '</t>');
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new \InvalidArgumentException('Invalid XML in template - error was: ' . $error->message);
		}

		$bbcode_id    = $m[1];
		$options      = array();
		$params       = array();
		$placeholders = array();
		$content      = false;

		if ($m[2])
		{
			$m[3] = $m[1] . $m[2] . $m[3];
		}

		if (isset($m[4]))
		{
			$identifier = $m[4];

			if (preg_match('#^\\{TEXT[0-9]*\\}$#D', $identifier))
			{
				/**
				* Use substring() to exclude the <st/> and <et/> children
				*/
				$placeholders[$identifier] = 'substring(., 1 + string-length(st), string-length() - (string-length(st) + string-length(et)))';
			}
			else
			{
				/**
				* We need to validate the content, means we should probably disable BBCodes, e.g.
				* [email]{EMAIL}[/email]
				*/
				$type  = rtrim(strtolower(substr($identifier, 1, -1)), '1234567890');
				$param = strtolower($bbcode_id);

				$options['default_rule']     = 'deny';
				$options['default_param']    = $param;
				$options['content_as_param'] = true;

				$params[$param] = array(
					'type'        => $type,
					'is_required' => true
				);
				$placeholders[$identifier] = '@' . $param;
			}
		}

		foreach (preg_split('#\\s+#', $m[3], null, \PREG_SPLIT_NO_EMPTY) as $pair)
		{
			list($param, $identifier) = explode('=', $pair);

			$param = strtolower($param);
			$type  = rtrim(strtolower(substr($identifier, 1, -1)), '1234567890');

			if (isset($params[$param]))
			{
				throw new \InvalidArgumentException('Param ' . $param . ' is defined twice');
			}

			if (isset($placeholders[$identifier]))
			{
				throw new \InvalidArgumentException('Placeholder ' . $identifier . ' is used twice');
			}

			$placeholders[$identifier] = '@' . $param;

			$params[$param] = array(
				'type'        => $type,
				'is_required' => false
			);
		}

		/**
		* Replace placeholders in attributes
		*/
		$xpath = new \DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attr)
		{
			$attr->value = preg_replace_callback(
				'#\\{[A-Z]+[0-9]*?\\}#',
				function ($m) use (&$placeholders, &$params, $bbcode_id, $flags)
				{
					$identifier = $m[0];

					if (!isset($placeholders[$identifier]))
					{
						throw new \Exception('Unknown placeholder ' . $identifier . ' found in template');
					}

					if (!($flags & ConfigBuilder::ALLOW_INSECURE_TEMPLATES)
					 && preg_match('#^\\{TEXT[0-9]*\\}$#D', $identifier))
					{
						throw new \Exception('Using {TEXT} inside HTML attributes is inherently insecure and has been disabled. Please pass ' . __CLASS__ . '::ALLOW_INSECURE_TEMPLATES as a third parameter to addBBCodeFromExample() to enable it');
					}

					$param = substr($placeholders[$identifier], 1);
					if (isset($params[$param]))
					{
						$params[$param]['is_required'] = true;
					}

					return '{' . $placeholders[$identifier] . '}';
				},
				$attr->value
			);
		}

		/**
		* Replace placeholders everywhere else: the lazy version
		*/
		$tpl = preg_replace_callback(
			'#\\{[A-Z]+[0-9]*\\}#',
			function ($m) use ($placeholders, &$params, $content)
			{
				if (!isset($placeholders[$m[0]]))
				{
					throw new \Exception('Unknown placeholder ' . $m[0] . ' found in template');
				}

				if ($placeholders[$m[0]][0] !== '@')
				{
					return '<xsl:apply-templates/>';
				}
				return '<xsl:value-of select="' . $placeholders[$m[0]] . '"/>';
			},
			substr($dom->saveXML($dom->documentElement), 3, -4)
		);

		$this->addBBCode($bbcode_id, $options);
		foreach ($params as $param => $_options)
		{
			$this->addBBCodeParam($bbcode_id, $param, $_options['type'], $_options['is_required']);
		}
		$this->setBBCodeTemplate($bbcode_id, $tpl, $flags);
	}

	protected function addInternalBBCode($prefix)
	{
		$prefix    = strtoupper($prefix);
		$bbcode_id = $prefix;
		$i         = 0;

		while (isset($this->bbcodes[$bbcode_id]) || isset($this->aliases[$bbcode_id]))
		{
			$bbcode_id = $prefix . $i;
			++$i;
		}

		$this->addBBCode($bbcode_id, array('internal_use' => true));
		return $bbcode_id;
	}

	/**
	* Takes a lowercased BBCode name and return a canonical BBCode ID with aliases resolved
	*
	* @param  string $bbcode_id BBCode name
	* @return string            BBCode ID, uppercased and with with aliases resolved
	*/
	protected function normalizeBBCodeId($bbcode_id)
	{
		$bbcode_id = strtoupper($bbcode_id);

		return (isset($this->bbcode_aliases[$bbcode_id])) ? $this->bbcode_aliases[$bbcode_id] : $bbcode_id;
	}

	//==========================================================================
	// Censor
	//==========================================================================

	public function setCensorOption($k, $v)
	{
		$this->setOption('Censor', $k, $v);
	}

	public function addCensor($word, $replacement = null)
	{
		/**
		* 0 00 word
		* 1 01 word*
		* 2 10 *word
		* 3 11 *word*
		*/
		$k = (($word[0] === '*') << 1) + (substr($word, -1) === '*');

		/**
		* Remove leading and trailing asterisks
		*/
		$word = trim($word, '*');
		$this->censor['words'][$k][] = $word;

		if (isset($replacement))
		{
			$mask = (($k & 2) ? '#' : '#^')
			      . str_replace('\\*', '.*', preg_quote($word, '#'))
			      . (($k & 1) ? '#i' : '$#iD');

			if (preg_match('#[\\x80-\\xFF]#', $word))
			{
				/**
				* Non-ASCII characters get the Unicode treatment
				*/
				$mask .= 'u';
			}

			$this->censor['replacements'][$k][$mask] = $replacement;
		}
	}

	public function getCensorConfig()
	{
		if (empty($this->censor))
		{
			return false;
		}

		
		if (!isset($this->passes['Censor']['bbcode'], $this->passes['Censor']['param']))
		{
			$bbcode_id = $this->addInternalBBCode('C');

			$this->addBBCodeParam($bbcode_id, 'with', 'text', false);

			$this->setCensorOption('bbcode', $bbcode_id);
			$this->setCensorOption('param', 'with');
		}

		$bbcode_id = $this->passes['Censor']['bbcode'];

		if (!isset($this->bbcodes[$bbcode_id]['tpl']))
		{
			$this->setBBCodeTemplate(
				$bbcode_id,
				'<xsl:choose><xsl:when test="@with"><xsl:value-of select="@with"/></xsl:when><xsl:otherwise>****</xsl:otherwise></xsl:choose>'
			);
		}

		$config = $this->passes['Censor'];

		foreach ($this->censor['words'] as $k => $words)
		{
			$regexp = self::buildRegexpFromList($words, array('*' => '\\pL*'));

			$config['regexp'][$k] = (($k & 2) ? '#\\pL*?' : '#\\b')
			                      . $regexp
			                      . (($k & 1) ? '\\pL*#i' : '\\b#i')
			                      . 'u';
		}

		if (isset($this->censor['replacements']))
		{
			$config['replacements'] = $this->censor['replacements'];
		}

		return $config;
	}

	//==========================================================================
	// Emoticons
	//==========================================================================

	/**
	* Add an emoticon
	*
	* @param string $code Emoticon code
	* @param string $tpl  Emoticon template, e.g. <img src="emot.png"/> -- must be well-formed XML
	*/
	public function addEmoticon($code, $tpl)
	{
		$this->emoticons[$code] = $tpl;
	}

	public function setEmoticonOption($k, $v)
	{
		$this->setOption('Emoticon', $k, $v);
	}

	public function getEmoticonConfig()
	{
		if (empty($this->emoticons))
		{
			return false;
		}

		if (!isset($this->passes['Emoticon']['bbcode']))
		{
			$this->setEmoticonOption('bbcode', $this->addInternalBBCode('E'));
		}

		$config = $this->passes['Emoticon'];

		/**
		* Create a template for this BBCode.
		* If one already exists, we overwrite it. That's how we roll
		*/
		$tpls = array();
		foreach ($this->emoticons as $code => $_tpl)
		{
			$tpls[$_tpl][] = $code;
		}

		$tpl = '<xsl:choose>';
		foreach ($tpls as $_tpl => $codes)
		{
			$tpl .= '<xsl:when test=".=\'' . implode("' or .='", $codes) . '\'">'
			      . $_tpl
			      . '</xsl:when>';
		}
		$tpl .= '<xsl:otherwise><xsl:value-of select="."/></xsl:otherwise></xsl:choose>';

		$this->setBBCodeTemplate($config['bbcode'], $tpl);

		// Non-anchored pattern, will benefit from the S modifier
		$config['regexp'] =	'#' . self::buildRegexpFromList(array_keys($this->emoticons)) . '#S';

		return $config;
	}

	//==========================================================================
	// Filters
	//==========================================================================

	public function setFilter($name, $callback, array $config = array())
	{
		if (!is_callable($callback))
		{
			throw new \InvalidArgumentException('The second argument passed to ' . __METHOD__ . ' is expected to be a valid callback');
		}

		$config['callback']   = $callback;
		$this->filters[$name] = $config;
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
		$this->filters['url']['disallowed_hosts'][]
			= preg_replace('#^\\*?\\.#', '', $host);
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
				throw new \InvalidArgumentException ("Invalid $k name '" . $v . "'");
			}

			if ($k === 'bbcode')
			{
				$v = $this->normalizeBBCodeId($v);

				if (!isset($this->bbcodes[$v]))
				{
					trigger_error('Unknown BBCode ' . $v, E_USER_NOTICE);
				}
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
					}
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
		if (!class_exists('parser'))
		{
			include_once(__DIR__ . '/Parser.php');
		}
		return new Parser($this->getParserConfig());
	}

	public function getRenderer()
	{
		if (!class_exists('renderer'))
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

	static public function isValidId($id)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*$#Di', $id);
	}

	public function getXSL()
	{
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . "\n"
			 . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
			 . '<xsl:output method="xml" encoding="utf-8" />'
			 . '<xsl:template match="/m">'
			 . '<xsl:for-each select="*">'
			 . '<xsl:apply-templates />'
			 . '<xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid" /></xsl:if>'
			 . '</xsl:for-each>'
			 . '</xsl:template>';

		foreach ($this->bbcodes as $bbcode_id => $bbcode)
		{
			if (isset($bbcode['tpl']))
			{
				$xsl .= $bbcode['tpl'];
			}
		}

		$xsl .= $this->xsl;
		$xsl .= '<xsl:template match="st" /><xsl:template match="et" /><xsl:template match="i" /></xsl:stylesheet>';

		return $xsl;
	}

	public function addXSL($xsl)
	{
		$xml = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $xsl
		     . '</xsl:stylesheet>';

		$old = libxml_use_internal_errors(true);
		$dom = new \DOMDocument;
		$res = $dom->loadXML($xml);
		libxml_use_internal_errors($old);

		if (!$res)
		{
			$error = libxml_get_last_error();
			throw new \InvalidArgumentException('Invalid XSL - error was: ' . $error->message);
		}

		$this->xsl .= $xsl;
	}
}