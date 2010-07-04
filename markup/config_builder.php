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
			'bbcode'       => 'URL',
			'param'        => 'href',
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
			'bbcode'       => 'CENSOR',
			'param'        => 'replacement',
			'limit'        => 1000,
			'limit_action' => 'warn'
		),
		'emoticon' => array(
			'parser'       => array('self', 'getEmoticonTags'),
			'bbcode'       => 'E',
			'param'        => 'code',
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

	public function setOption($pass, $k, $v)
	{
		$this->passes[$pass][$k] = $v;
	}

	//==========================================================================
	// Autolink
	//==========================================================================

	public function getAutolinkConfig()
	{
		$config = $this->passes['autolink'];

		if (!isset()
		{
		}
		return  + array(
			'regexp' => '#' . self::buildRegexpFromList($this->filters['url']['allowed_schemes']) . '://\\S+#iS'
		);
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
		if (!$this->isValidId($bbcode_id))
		{
			throw new \InvalidArgumentException ("Invalid BBCode name '" . $bbcode_id . "'");
		}

		$bbcode_id = strtoupper($bbcode_id);

		if (isset($this->bbcodes[$bbcode_id]))
		{
			throw new \Exception('BBCode ' . $bbcode_id . ' already exists');
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
			throw new \Exception("Unknown BBCode '" . $bbcode_id . "'");
		}
		if (isset($this->bbcodes[$alias]))
		{
			throw new \Exception("Cannot create alias '" . $alias . "' - a BBCode using that name already exists");
		}

		/**
		* For the time being, restrict aliases to a-z, 0-9, _ and * with no restriction on first char
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
			throw new \Exception("Unknown BBCode '" . $bbcode_id . "'");
		}

		$param_name = strtolower($param_name);
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
		if (empty($this->bbcodes))
		{
			return false;
		}

		$config = $this->passes['bbcode'];
		$config['aliases'] = $this->bbcode_aliases;
		$config['bbcodes'] = $this->bbcodes;

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
			'#\\[/?(' . preg_replace('#^\\(\\?:(.*)\\)$#D', '$1', $regexp) . ')(?=[\\] =:])#i';

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

		$config = array();

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
		$this->emoticons[$code] = preg_quote($code, '#');
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

		if (!isset($this->bbcodes[$config['bbcode']]))
		{
			throw new \Exception('Emoticons require a BBCode named ' . $config['bbcode'] . ' which has not been declared');
		}

		// Non-anchored pattern, will benefit from the S modifier
		$config['regexp'] = '#' . self::buildRegexpFromList($this->emoticons) . '#S';

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

	public function getParserConfig()
	{
		$config = array();

		foreach ($this->passes as $pass => $conf)
		{
			if (!isset($conf['parser']))
			{
				trigger_error("Skipping unknown BBCode option '" . $k . "'", E_USER_NOTICE);
				continue;
			}
		}
		return array_filter(array(
			'bbcode'   => $this->getBBCodeConfig(),
			'autolink' => $this->getAutolinkConfig(),
			'censor'   => $this->getCensorConfig(),
			'emoticons'  => $this->getEmoticonsConfig(),

			'filters'  => $this->getFiltersConfig()
		));
	}

	public function isValidId($id)
	{
		return (bool) preg_match('#^[a-z][a-z_0-9]*#Di', $id);
	}
}