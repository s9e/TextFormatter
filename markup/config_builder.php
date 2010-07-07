<?php

/**
* @package   s9e\toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\toolkit\markup;

class config_builder
{
	protected $passes = array(
		'autolink' => array(
			'parser'       => array('self', 'getAutolinkTags'),
			'limit'        => 1000,
			'limit_action' => 'ignore'
		),
		'bbcode' => array(
			'parser'       => array('self', 'getBBCodeTags'),
			'limit'        => 1000,
			'limit_action' => 'ignore'
		),
		'censor' => array(
			'parser'       => array('self', 'getCensorTags'),
			'limit'        => 1000,
			'limit_action' => 'warn'
		),
		'emoticon' => array(
			'parser'       => array('self', 'getEmoticonTags'),
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

	//==========================================================================
	// Autolink
	//==========================================================================

	public function getAutolinkConfig()
	{
		$config = $this->passes['autolink'];

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
		$this->setOption('bbcode', $k, $v);
	}

	public function addBBCode($bbcode_id, array $options = array())
	{
		if (!self::isValidId($bbcode_id))
		{
			throw new \InvalidArgumentException ("Invalid BBCode name '" . $bbcode_id . "'");
		}

		$bbcode_id = strtoupper($bbcode_id);

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
			elseif ($k !== 'default_param' && $k !== 'internal_use')
			{
				trigger_error("Skipping unknown BBCode option '" . $k . "'", E_USER_NOTICE);
				continue;
			}

			$bbcode[$k] = $v;
		}

		$this->bbcodes[$bbcode_id] = $bbcode;
		$this->bbcode_aliases[$bbcode_id] = $bbcode_id;
	}

	public function addBBCodeAlias($bbcode_id, $alias)
	{
		$bbcode_id = strtoupper($bbcode_id);
		$alias     = strtoupper($alias);

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
		$bbcode_id = strtoupper($bbcode_id);
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

		$bbcode_id = strtoupper($bbcode_id);
		$target    = strtoupper($target);

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
		$config = $this->passes['bbcode'];
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

	public function setBBCodeTemplate($bbcode_id, $tpl, $allow_insecure = '')
	{
		$bbcode_id = strtoupper($bbcode_id);
		if (!isset($this->bbcodes[$bbcode_id]))
		{
			throw new \InvalidArgumentException("Unknown BBCode '" . $bbcode_id . "'");
		}

		$tpl = '<xsl:template match="' . $bbcode_id . '">' . $tpl . '</xsl:template>';

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

		if ($allow_insecure !== 'ALLOW_INSECURE_TEMPLATES')
		{
			$xpath = new \DOMXPath($dom);

			if ($xpath->query('//script[contains(@src, "{") or .//xsl:value-of]')->length)
			{
				throw new \Exception('It seems that your template contains <script> tag using user-supplied information. Those can be insecure and are disabled by default. Please pass "ALLOW_INSECURE_TEMPLATES" as a third parameter to setBBCodeTemplate() to enable it');
			}

			foreach ($xpath->query('//@style[contains(., "{")]') as $attr)
			{
				print_r($attr);
				exit;
			}
		}

# //@style[contains(., "{")] |
		$this->bbcodes[$bbcode_id]['tpl'] = $tpl;
	}

	public function addBBCodeFromExample($def, $tpl, $allow_insecure = '')
	{
		$regexp = '#'
		        . '\\[([a-zA-Z_][a-zA-Z_0-9]*)(=\\{[A-Z_]+\\})?'
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
				function ($m) use (&$placeholders, &$params, $bbcode_id, $allow_insecure)
				{
					$identifier = $m[0];

					if (!isset($placeholders[$identifier]))
					{
						throw new \Exception('Unknown placeholder ' . $identifier . ' found in template');
					}

					if ($allow_insecure !== 'ALLOW_INSECURE_TEMPLATES'
					 && preg_match('#^\\{TEXT[0-9]*\\}$#D', $identifier))
					{
						throw new \Exception('Using {TEXT} inside HTML attributes is inherently insecure and has been disabled. Please pass "ALLOW_INSECURE_TEMPLATES" as a third parameter to addBBCodeFromExample() to enable it');
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
		$this->setBBCodeTemplate($bbcode_id, $tpl, $allow_insecure);
	}

	//==========================================================================
	// Censor
	//==========================================================================

	public function setCensorOption($k, $v)
	{
		$this->setOption('censor', $k, $v);
	}

	public function addCensor($word, $replacement = null)
	{
		/**
		* 0 00 word
		* 1 01 word*
		* 2 10 *word
		* 3 11 *word*
		*/
		$k = ($word[0] === '*') << 1 + (substr($word, -1) === '*');

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

		$config = $this->passes['censor'];

		if (!isset($config['bbcode']))
		{
			trigger_error('No BBCode assigned to the censor pass, it will be disabled', E_USER_WARNING);
			return false;
		}
		if (!isset($config['param']))
		{
			trigger_error('No BBCode param assigned to the censor pass, it will be disabled', E_USER_WARNING);
			return false;
		}

		foreach ($this->censor['words'] as $k => $words)
		{
			$regexp = self::buildRegexpFromList($words, array('*' => '\\w*'));
			$pm     = (preg_match('#[\\x80-\\xff]#', $regexp)) ? 'u' : '';

			$config['regexp'][$k] = (($k & 2) ? '#\\w*?' : '#\\b')
			                      . $regexp
			                      . (($k & 1) ? '\\w*#i' : '\\b#i')
			                      . $pm;
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

	public function addEmoticon($code)
	{
		$this->emoticons[$code] = array();
	}

	public function setEmoticonOption($k, $v)
	{
		$this->setOption('emoticon', $k, $v);
	}

	public function getEmoticonConfig()
	{
		if (empty($this->emoticons))
		{
			return false;
		}

		$config = $this->passes['emoticon'];

		if (!isset($config['bbcode']))
		{
			trigger_error('No BBCode assigned to the emoticon pass, it will be disabled', E_USER_WARNING);
			return false;
		}
		if (!isset($config['param']))
		{
			trigger_error('No BBCode param assigned to the emoticon pass, it will be disabled', E_USER_WARNING);
			return false;
		}

		// Non-anchored pattern, will benefit from the S modifier
		$config['regexp'] =	'#' . self::buildRegexpFromList(array_keys($this->emoticons)) . '#S';

		if (preg_match('#[\\x80-\\xFF]#', $config['regexp']))
		{
			$config['regexp'] .= 'u';
		}

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
		$this->filters['url']['disallowed_hosts'][] = $host;
	}

	public function getFiltersConfig()
	{
		$filters = $this->filters;

		$filters['url']['allowed_schemes']
			= '#' . self::buildRegexpFromList($filters['url']['allowed_schemes']) . '$#ADi';

		if (isset($filters['url']['disallowed_hosts']))
		{
			$filters['url']['disallowed_hosts'] =
				'#' . self::buildRegexpFromList($filters['url']['disallowed_hosts']) . '$#DiS';
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
				$v = strtoupper($v);

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
			include_once(__DIR__ . '/parser.php');
		}
		return new parser($this->getParserConfig());
	}

	public function getRenderer()
	{
		if (!class_exists('renderer'))
		{
			include_once(__DIR__ . '/renderer.php');
		}
		return new renderer($this->getXSL());
	}

	public function getParserConfig()
	{
		$config = array();

		foreach ($this->passes as $pass => $conf)
		{
			if (!isset($conf['parser']))
			{
				trigger_error("Skipping pass '" . $k . "' - no parser given", E_USER_NOTICE);
				continue;
			}
		}
		return array_filter(array(
			'bbcode'   => $this->getBBCodeConfig(),
			'autolink' => $this->getAutolinkConfig(),
			'censor'   => $this->getCensorConfig(),
			'emoticon' => $this->getEmoticonConfig(),

			'filters'  => $this->getFiltersConfig()
		));
	}

	static public function isValidId($id)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9]*#Di', $id);
	}

	public function getXSL()
	{
		$xsl = '<?xml version="1.0" encoding="utf-8"?><xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"><xsl:output method="xml" encoding="utf-8" />';

		foreach ($this->bbcodes as $bbcode_id => $bbcode)
		{
			if (isset($bbcode['tpl']))
			{
				$xsl .= $bbcode['tpl'];
			}
		}

		$xsl .= '<xsl:template match="st" /><xsl:template match="et" /></xsl:stylesheet>';

		return $xsl;
	}
}