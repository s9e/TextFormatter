<?php

/**
* @package   s9e\toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\toolkit\markup;

class parser
{
	/**
	* Opening tag, e.g. [b]
	* -- becomes <B><st>[b]</st>
	*/
	const TAG_OPEN  = 1;

	/**
	* Closing tag, e.g. [/b]
	* -- becomes <et>[/b]</et></B>
	*/
	const TAG_CLOSE = 2;

	/**
	* Self-closing tag, e.g. [img="http://..." /]
	* -- becomes <IMG>[img="http://..." /]</IMG>
	*
	* NOTE: TAG_SELF = TAG_OPEN | TAG_CLOSE
	*/
	const TAG_SELF  = 3;

	/**
	* @var	array
	*/
	public $msgs;

	/**
	* @var	array
	*/
	protected $config;

	public function __construct(array $config)
	{
		$this->filters = $config['filters'];
		unset($config['filters']);

		$this->config = $config;
	}

	public function parse($text, $writer = '\\XMLWriter')
	{
		$this->msgs = $tags = array();

		$pass = 0;
		foreach ($this->config as $config)
		{
			if (isset($config['parser']))
			{
				$ret = call_user_func($config['parser'], $text, $config);

				if (!empty($ret['msgs']))
				{
					$this->msgs = array_merge_recursive($this->msgs, $ret['msgs']);
				}

				if (!empty($ret['tags']))
				{
					foreach ($ret['tags'] as $tag)
					{
						if (!isset($tag['suffix']))
						{
							/**
							* Add a suffix to tags that don't have one so that closing tags from a
							* pass don't close tags opened by another pass
							*/
							$tag['suffix'] = '-' . $pass;
						}
						$tag['pass'] = $pass;
						$tags[]      = $tag;
					}
				}

				++$pass;
			}
		}

		$xml = new $writer;
		$xml->openMemory();

		if (empty($tags))
		{
			$xml->writeElement('pt', $text);
			return trim($xml->outputMemory(true));
		}

		/**
		* Sort by pos descending, tag type ascending (OPEN, CLOSE, SELF), pass descending
		*/
		usort($tags, function($a, $b)
		{
			return ($b['pos'] - $a['pos'])
			    ?: ($a['type'] - $b['type'])
			    ?: ($b['pass'] - $a['pass']);
		});


		//======================================================================
		// Time to get serious
		//======================================================================

		$aliases  = $this->config['bbcode']['aliases'];
		$bbcodes  = $this->config['bbcode']['bbcodes'];

		/**
		* @var	array	Open BBCodes
		*/
		$bbcode_stack = array();

		/**
		* @var	array	List of allowed BBCode tags in current context. Starts as a copy of $aliases
		*/
		$allowed = $aliases;

		/**
		* @var	array	Number of times each BBCode has been used
		*/
		$cnt_total = array_fill_keys($allowed, 0);

		/**
		* @var	array	Number of open tags for each bbcode_id
		*/
		$cnt_open = $cnt_total;

		/**
		* @var	array	Keeps track open tags (tags carry their suffix)
		*/
		$open_tags = array();

		$xml->startElement('rt');

		$pos = 0;
		do
		{
			$tag = array_pop($tags);

			if ($pos > $tag['pos'])
			{
				$this->msgs['debug'][] = array(
					'pos'    => $tag['pos'],
					'msg'    => 'Tag skipped',
					'params' => array()
				);
				continue;
			}

			$bbcode_id = $tag['name'];
			if (!isset($bbcodes[$bbcode_id]))
			{
				$bbcode_id = strtoupper($bbcode_id);

				if (!isset($aliases[$bbcode_id]))
				{
					$this->msgs['debug'][] = array(
						'pos'    => $tag['pos'],
						'msg'    => 'Unknown BBCode %1$s from pass %2$s',
						'params' => array($bbcode_id, $tag['pass'])
					);
					continue;
				}

				$bbcode_id = $aliases[$bbcode_id];
			}

			$bbcode = $bbcodes[$bbcode_id];
			$suffix = (isset($tag['suffix'])) ? $tag['suffix'] : '';

			//==================================================================
			// Start tag
			//==================================================================

			if ($tag['type'] & self::TAG_OPEN)
			{
				//==============================================================
				// Check that this BBCode is allowed here
				//==============================================================

				if (!empty($bbcode['close_parent']))
				{
					/**
					* Oh, wait, we may have to close its parent first
					*/
					$last_bbcode = end($bbcode_stack);
					foreach ($bbcode['close_parent'] as $parent)
					{
						if ($last_bbcode['bbcode_id'] === $parent)
						{
							/**
							* So we do have to close that parent. First we reinsert current tag then
							* we append a new closing tag for the parent.
							*/
							$tags[] = $tag;
							$tags[] = array(
								'pos'  => $tag['pos'],
								'name' => $parent,
								'len'  => 0,
								'type' => self::TAG_CLOSE
							);
							continue 2;
						}
					}
				}

				if ($bbcode['nesting_limit'] <= $cnt_open[$bbcode_id]
				 || $bbcode['tag_limit']     <= $cnt_total[$bbcode_id])
				{
					continue;
				}

				if (!isset($allowed[$bbcode_id]))
				{
					$this->msgs['debug'][] = array(
						'pos'    => $tag['pos'],
						'msg'    => 'BBCode %s is not allowed in this context',
						'params' => array($bbcode_id)
					);
					continue;
				}

				if (isset($bbcode['require_parent']))
				{
					$last_bbcode = end($bbcode_stack);

					if (!$last_bbcode
					 || $last_bbcode['bbcode_id'] !== $bbcode['require_parent'])
					{
						$this->msgs['debug'][] = array(
							'pos'    => $tag['pos'],
							'msg'    => 'BBCode %1$s requires %2$s as parent',
							'params' => array($bbcode_id, $bbcode['require_parent'])
						);

						continue;
					}
				}

				if (isset($bbcode['require_ascendant']))
				{
					foreach ($bbcode['require_ascendant'] as $ascendant)
					{
						if (empty($cnt_open[$ascendant]))
						{
							$this->msgs['debug'][] = array(
								'pos'    => $tag['pos'],
								'msg'    => 'BBCode %1$s requires %2$s as ascendant',
								'params' => array($bbcode_id, $ascendant)
							);
							continue 2;
						}
					}
				}

				if (isset($bbcode['params']))
				{
					/**
					* Check for missing required params
					*/
					foreach (array_diff_key($bbcode['params'], $tag['params']) as $param => $param_conf)
					{
						if (empty($param_conf['is_required']))
						{
							continue;
						}

						$msgs['error'][] = array(
							'pos'    => $tag['pos'],
							'msg'    => 'Missing param %s',
							'params' => array($param)
						);

						continue 2;
					}

					$invalid = array();
					foreach ($tag['params'] as $k => &$v)
					{
						$v = $this->filter($v, $bbcode['params'][$k]['type']);

						if ($v === false)
						{
							$msgs['error'][] = array(
								'pos'    => $tag['pos'],
								'msg'    => 'Invalid param %s',
								'params' => array($k)
							);

							if ($bbcode['params'][$k]['is_required'])
							{
								// Skip this tag
								continue 2;
							}

							$invalid[] = $k;
						}
					}

					foreach ($invalid as $k)
					{
						unset($tag['params'][$k]);
					}
				}

				//==============================================================
				// Ok, so we have a valid BBCode, we can append it to the XML
				//==============================================================

				if ($tag['pos'] !== $pos)
				{
					$xml->text(substr($text, $pos, $tag['pos'] - $pos));
				}
				$pos = $tag['pos'] + $tag['len'];

				++$cnt_total[$bbcode_id];

				$xml->startElement($bbcode_id);
				if (!empty($tag['params']))
				{
					/**
					* Sort params alphabetically. Can be useful if someone wants to process the
					* output using regexp
					*/
					ksort($tag['params']);
					foreach ($tag['params'] as $param => $value)
					{
						$xml->writeAttribute($param, $value);
					}
				}

				if ($tag['type'] & self::TAG_CLOSE)
				{
					if ($tag['len'])
					{
						$xml->text(substr($text, $tag['pos'], $tag['len']));
					}
					$xml->endElement();
					continue;
				}

				if ($tag['len'])
				{
					$xml->writeElement('st', substr($text, $tag['pos'], $tag['len']));
				}

				++$cnt_open[$bbcode_id];

				if (isset($open_tags[$bbcode_id . $suffix]))
				{
					++$open_tags[$bbcode_id . $suffix];
				}
				else
				{
					$open_tags[$bbcode_id . $suffix] = 1;
				}

				$bbcode_stack[] = array(
					'bbcode_id' => $bbcode_id,
					'suffix'	=> $suffix,
					'allowed'   => $allowed
				);
				$allowed = array_intersect_key($allowed, $bbcode['allow']);
			}

			//==================================================================
			// End tag
			//==================================================================

			if ($tag['type'] & self::TAG_CLOSE)
			{
				if (empty($open_tags[$bbcode_id . $suffix]))
				{
					/**
					* This is an end tag but there's no matching start tag
					*/
					$this->msgs['debug'][] = array(
						'pos'    => $tag['pos'],
						'msg'    => 'Could not find a matching start tag for BBCode %s',
						'params' => array($bbcode_id . $suffix)
					);
					continue;
				}

				if ($tag['pos'] > $pos)
				{
					$xml->text(substr($text, $pos, $tag['pos'] - $pos));
				}

				$pos = $tag['pos'] + $tag['len'];

				do
				{
					$cur     = array_pop($bbcode_stack);
					$allowed = $cur['allowed'];

					--$cnt_open[$cur['bbcode_id']];
					--$open_tags[$cur['bbcode_id'] . $cur['suffix']];

					if ($cur['bbcode_id'] === $bbcode_id)
					{
						if ($tag['len'])
						{
							$xml->writeElement('et', substr($text, $tag['pos'], $tag['len']));
						}
						$xml->endElement();
						break;
					}
					$xml->endElement();
				}
				while ($cur);
			}
		}
		while (!empty($tags));

		if ($pos)
		{
			if (isset($text[$pos]))
			{
				$xml->text(substr($text, $pos));
			}
			$xml->endDocument();
		}
		else
		{
			/**
			* If there was no valid tag, we rewrite the XML as a <pt/> element
			*/
			$xml = new $writer;
			$xml->openMemory();

			$xml->writeElement('pt', $text);
		}

		return trim($xml->outputMemory(true));
	}

	public function filter($var, $type)
	{
		if (isset($this->filters[$type]['callback']))
		{
			return call_user_func($this->filters[$type]['callback'], $var, $this->filters[$type]);
		}

		switch ($type)
		{
			case 'url':
				$var = filter_var($var, \FILTER_VALIDATE_URL);

				if (!$var)
				{
					return false;
				}

				$p = parse_url($var);

				if (!preg_match($this->filters['url']['allowed_schemes'], $p['scheme']))
				{
					return false;
				}

				if (isset($this->filters['url']['disallowed_hosts'])
				 && preg_match($this->filters['url']['disallowed_hosts'], $p['host']))
				{
					return false;
				}

				/**
				* We escape quotes just in case someone would want to use the URL in some Javascript
				* thingy
				*/
				return str_replace(array("'", '"'), array('%27', '%22'), $var);

			case 'text':
				return $var;

			case 'email':
				return filter_var($var, \FILTER_VALIDATE_EMAIL);

			case 'number':
				if (!is_numeric($var))
				{
					return false;
				}
				return (float) $var;

			case 'int':
			case 'integer':
				return filter_var($var, \FILTER_VALIDATE_INT);

			case 'uint':
				return filter_var($var, \FILTER_VALIDATE_INT, array(
					'options' => array('min_range' => 0)
				));

			case 'color':
				return (preg_match('/^(?:#[0-9a-f]{3,6}|[a-z]+)$/Di', $var)) ? $var : false;

			default:
				$this->msgs['debug'][] = array(
					'msg'    => 'Unknown filter %s',
					'params' => array($type)
				);
				return false;
		}
	}

	//==========================================================================
	// Tokenizers
	//==========================================================================

	static public function getAutolinkTags($text, array $config)
	{
		$tags = array();
		$msgs = array();
		$cnt  = preg_match_all($config['regexp'], $text, $matches, PREG_OFFSET_CAPTURE);

		if (!$cnt)
		{
			return;
		}

		if (!empty($config['limit'])
		 && $cnt > $config['limit'])
		{
			if ($config['limit_action'] === 'abort')
			{
				throw new \RuntimeException('Autolink limit exceeded');
			}
			else
			{
				$msg_type   = ($config['limit_action'] === 'ignore') ? 'debug' : 'warning';
				$matches[0] = array_slice($matches[0], 0, $config['limit']);

				$msgs[$msg_type][] = array(
					'pos'    => 0,
					'msg'    => 'Autolink limit exceeded. Only the first %s links will be processed',
					'params' => array($config['limit'])
				);
			}
		}

		$bbcode = $config['bbcode'];
		$param  = $config['param'];

		foreach ($matches[0] as $m)
		{
			$url = $m[0];

			/**
			* Remove some trailing punctuation. We preserve right parentheses if there's a left
			* parenthesis in the URL, as in http://en.wikipedia.org/wiki/Mars_(disambiguation) 
			*/
			$url   = rtrim($url);
			$rtrim = (strpos($url, '(')) ? '.' : ').';
			$url   = rtrim($url, $rtrim);

			$tags[] = array(
				'pos'    => $m[1],
				'name'   => $bbcode,
				'type'   => self::TAG_OPEN,
				'len'    => 0,
				'params' => array($param => $url)
			);
			$tags[] = array(
				'pos'    => $m[1] + strlen($url),
				'name'   => $bbcode,
				'type'   => self::TAG_CLOSE,
				'len'    => 0
			);
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}

	static public function getBBCodeTags($text, array $config)
	{
		$tags = array();
		$msgs = array();
		$cnt  = preg_match_all($config['regexp'], $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);

		if (!$cnt)
		{
			return array(
				'tags' => array(),
				'msgs' => array()
			);
		}

		if (!empty($config['limit'])
		 && $cnt > $config['limit'])
		{
			if ($config['limit_action'] === 'abort')
			{
				throw new \RuntimeException('BBCode tags limit exceeded');
			}
			else
			{
				$msg_type = ($config['limit_action'] === 'ignore') ? 'debug' : 'warning';
				$matches  = array_slice($matches, 0, $config['limit']);

				$msgs[$msg_type][] = array(
					'pos'    => 0,
					'msg'    => 'BBCode tags limit exceeded. Only the first %s tags will be processed',
					'params' => array($config['limit'])
				);
			}
		}

		$bbcodes  = $config['bbcodes'];
		$aliases  = $config['aliases'];
		$text_len = strlen($text);

		foreach ($matches as $m)
		{
			/**
			* @var Position of the first character of current BBCode, which should be a [
			*/
			$lpos = $m[0][1];

			/**
			* @var Position of the last character of current BBCode, starts as the position of
			*      the =, ] or : char, then moves to the right as the BBCode is parsed
			*/
			$rpos = $lpos + strlen($m[0][0]);

			/**
			* Check for BBCode suffix
			*
			* Used to skip the parsing of closing BBCodes, e.g.
			*   [code:1][code]type your code here[/code][/code:1]
			*
			*/
			if ($text[$rpos] === ':')
			{
				/**
				* [code:1] or [/code:1]
				* $suffix = ':1'
				*/
				$spn     = strspn($text, '1234567890', 1 + $rpos);
				$suffix  = substr($text, $rpos, 1 + $spn);
				$rpos   += 1 + $spn;
			}
			else
			{
				$suffix  = '';
			}

			$alias = strtoupper($m[1][0]);

			if (!isset($aliases[$alias]))
			{
				// Not a known BBCode or alias
				continue;
			}

			$bbcode_id = $aliases[$alias];
			$bbcode    = $bbcodes[$bbcode_id];
			$params    = array();

			if (!empty($bbcode['internal_use']))
			{
				/**
				* This is theorically impossible, as the regexp does not contain internal BBCodes.
				*/
				if ($m[0][0][1] !== '/')
				{
					/**
					* We only warn about starting tags, no need to raise 2 warnings per pair
					*/
					$msgs['warning'][] = array(
						'pos'    => $lpos,
						'msg'    => 'BBCode %s is for internal use only',
						'params' => array($bbcode_id)
					);
				}
				continue;
			}
			// @codeCoverageIgnoreEnd

			if ($m[0][0][1] === '/')
			{
				if ($text[$rpos] !== ']')
				{
					$msgs['warning'][] = array(
						'pos'    => $rpos,
						'msg'    => 'Unexpected character %s',
						'params' => array($text[$rpos])
					);
					continue;
				}

				$type = self::TAG_CLOSE;
			}
			else
			{
				$type        = self::TAG_OPEN;
				$well_formed = false;
				$param       = null;

				if ($text[$rpos] === '=')
				{
					/**
					* [quote=
					*
					* Set the default param. If there's no default param, we issue a warning and
					* reuse the BBCode's name instead
					*/
					if (isset($bbcode['default_param']))
					{
						$param = $bbcode['default_param'];
					}
					else
					{
						$param = strtolower($bbcode_id);

						$msgs['warning'][] = array(
							'pos'    => $rpos,
							'msg'    => "BBCode %s does not have a default param, using BBCode's name as param name",
							'params' => array($bbcode_id)
						);
					}

					++$rpos;
				}

				while ($rpos < $text_len)
				{
					$c = $text[$rpos];

					if ($c === ']' || $c === '/')
					{
						/**
						* We're closing this tag
						*/
						if (isset($param))
						{
							/**
							* [quote=]
							* [quote username=]
							*/
							$msgs['warning'][] = array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($c)
							);
							continue 2;
						}

						if ($c === '/')
						{
							/**
							* Self-closing tag, e.g. [foo/]
							*/
							$type = self::TAG_SELF;
							++$rpos;

							if ($rpos === $text_len)
							{
								// text ends with [some tag/
								continue 2;
							}

							$c = $text[$rpos];
							if ($c !== ']')
							{
								$msgs['warning'][] = array(
									'pos'    => $rpos,
									'msg'    => 'Unexpected character: expected ] found %s',
									'params' => array($c)
								);
								continue 2;
							}
						}

						$well_formed = true;
						break;
					}

					if ($c === ' ')
					{
						++$rpos;
						continue;
					}

					if (!isset($param))
					{
						/**
						* Capture the param name
						*/
						$spn = strspn($text, 'abcdefghijklmnopqrstuvwxyz_ABCDEFGHIJKLMNOPQRSTUVWXYZ', $rpos);

						if (!$spn)
						{
							$msgs['warning'][] = array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($c)
							);
							continue 2;
						}

						if ($rpos + $spn >= $text_len)
						{	
							$msgs['debug'][] = array(
								'pos'    => $rpos,
								'msg'    => 'Param name seems to extend till the end of $text',
								'params' => array()
							);
							continue 2;
						}

						$param = strtolower(substr($text, $rpos, $spn));
						$rpos += $spn;

						if ($text[$rpos] !== '=')
						{
							$msgs['warning'][] = array(
								'pos'    => $rpos,
								'msg'    => 'Unexpected character %s',
								'params' => array($text[$rpos])
							);
							continue 2;
						}

						++$rpos;
						continue;
					}

					if ($c === '"' || $c === "'")
					{
						$value_pos = $rpos + 1;

						while (++$rpos < $text_len)
						{
							$rpos = strpos($text, $c, $rpos);

							if ($rpos === false)
							{
								/**
								* No matching quote, apparently that string never ends...
								*/
								$msgs['error'][] = array(
									'pos' => $value_pos - 1,
									'msg' => 'Could not find matching quote'
								);
								continue 3;
							}

							if ($text[$rpos - 1] === '\\')
							{
								$n = 1;
								do
								{
									++$n;
								}
								while ($text[$rpos - $n] === '\\');

								if ($n % 2 === 0)
								{
									continue;
								}
							}

							break;
						}

						$value = stripslashes(substr($text, $value_pos, $rpos - $value_pos));

						// Skip past the closing quote
						++$rpos;
					}
					else
					{
						$spn   = strcspn($text, "] \n\r", $rpos);
						$value = substr($text, $rpos, $spn);

						$rpos += $spn;
					}

					if (isset($bbcode['params'][$param]))
					{
						/**
						* We only keep params that exist in the BBCode's definition
						*/
						$params[$param] = $value;
					}

					unset($param, $value);
				}

				if (!$well_formed)
				{
					continue;
				}

				if ($type === self::TAG_OPEN
				 && isset($bbcode['default_param'])
				 && !isset($params[$bbcode['default_param']])
				 && !empty($bbcode['content_as_param']))
				{
					/**
					* Capture the content of that tag and use it as param
					*
					* @todo insert the corresponding closing tag now, to ensure that we captured
					*       exactly what will end up being this tag pair's content. Would make a
					*       difference in [a][b="[/a]"][/b][/a]
					*
					* @todo perhaps disable all BBCodes when the content is used as param? how?
					*/
					$pos = stripos($text, '[/' . $bbcode_id . $suffix . ']', $rpos);

					if ($pos)
					{
						$params[$bbcode['default_param']]
							= substr($text, 1 + $rpos, $pos - (1 + $rpos));
					}
				}
			}

			$tags[] = array(
				'name'   => $bbcode_id,
				'pos'    => $lpos,
				'len'    => $rpos + 1 - $lpos,
				'type'   => $type,
				'suffix' => $suffix,
				'params' => $params
			);
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}

	static public function getCensorTags($text, array $config)
	{
		$bbcode = $config['bbcode'];
		$param  = $config['param'];

		$cnt   = 0;
		$tags  = array();
		$msgs  = array();
		$break = false;

		foreach ($config['regexp'] as $k => $regexp)
		{
			if (substr($regexp, -1) !== 'u')
			{
				/**
				* The regexp isn't Unicode-aware, does $text contain more than ASCII?
				*/
				if (!isset($is_utf8))
				{
					$is_utf8 = preg_match('#[\\x80-\\xff]#', $text);
				}

				if ($is_utf8)
				{
					/**
					* Note: we assume that censored words don't contain backslashes, so there should
					*       not be any escaped backslash in the regexp
					*/
					$regexp = str_replace('\\w*', '\\pL*', $regexp) . 'u';
				}
			}

			$_cnt = preg_match_all($regexp, $text, $matches, PREG_OFFSET_CAPTURE);

			if (!$_cnt)
			{
				continue;
			}

			$cnt += $_cnt;

			if (!empty($config['limit'])
			 && $cnt > $config['limit'])
			{
				if ($config['limit_action'] === 'abort')
				{
					throw new \RuntimeException('Censor limit exceeded');
				}
				else
				{
					$limit      = $config['limit'] + $_cnt - $cnt;
					$msg_type   = ($config['limit_action'] === 'ignore') ? 'debug' : 'warning';
					$matches[0] = array_slice($matches[0], 0, $limit);

					$msgs[$msg_type][] = array(
						'pos'    => 0,
						'msg'    => 'Censor limit exceeded. Only the first %s matches will be processed',
						'params' => array($config['limit'])
					);

					$break = true;
				}
			}

			$replacements = (isset($config['replacements'][$k])) ? $config['replacements'][$k] : array();

			foreach ($matches[0] as $m)
			{
				$tag = array(
					'pos'  => $m[1],
					'name' => $bbcode,
					'type' => self::TAG_SELF,
					'len'  => strlen($m[0])
				);

				foreach ($replacements as $mask => $replacement)
				{
					if (preg_match($mask, $m[0]))
					{
						$tag['params'][$param] = $replacement;
						break;
					}
				}

				$tags[] = $tag;
			}

			if ($break)
			{
				break;
			}
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}

	static public function getEmoticonTags($text, array $config)
	{
		$cnt = preg_match_all($config['regexp'], $text, $matches, PREG_OFFSET_CAPTURE);

		if (!$cnt)
		{
			return;
		}

		$tags = array();
		$msgs = array();

		if (!empty($config['limit'])
		 && $cnt > $config['limit'])
		{
			if ($config['limit_action'] === 'abort')
			{
				throw new \RuntimeException('Emoticon limit exceeded');
			}
			else
			{
				$msg_type   = ($config['limit_action'] === 'ignore') ? 'debug' : 'warning';
				$matches[0] = array_slice($matches[0], 0, $config['limit']);

				$msgs[$msg_type][] = array(
					'pos'    => 0,
					'msg'    => 'Emoticon limit exceeded. Only the first %s emoticons will be processed',
					'params' => array($config['limit'])
				);
			}
		}

		$bbcode = $config['bbcode'];
		$param  = $config['param'];

		foreach ($matches[0] as $m)
		{
			$tags[] = array(
				'pos'    => $m[1],
				'type'   => self::TAG_SELF,
				'name'   => $bbcode,
				'len'    => strlen($m[0]),
				'params' => array($param => $m[0])
			);
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}
}