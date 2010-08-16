<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\Markup;

class Parser
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
	protected $passes;

	/**
	* @var	array
	*/
	protected $filters;

	public function __construct(array $config)
	{
		$this->passes  = $config['passes'];
		$this->filters = $config['filters'];
	}

	public function parse($text, $writer = '\\XMLWriter')
	{
		$this->msgs = $tags = array();

		$pass = 0;
		foreach ($this->passes as $name => $config)
		{
			$matches = array();
			if (isset($config['regexp']))
			{
				$isArray = is_array($config['regexp']);

				$cnt  = 0;
				$skip = false;

				foreach ((array) $config['regexp'] as $k => $regexp)
				{
					if ($skip)
					{
						$matches[$k] = array();
						continue;
					}

					$_cnt = preg_match_all($regexp, $text, $matches[$k], \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);

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
							throw new \RuntimeException($name . ' limit exceeded');
						}
						else
						{
							$limit       = $config['limit'] + $_cnt - $cnt;
							$msgType     = ($config['limit_action'] === 'ignore') ? 'debug' : 'warning';
							$matches[$k] = array_slice($matches[$k], 0, $limit);

							$msgs[$msgType][] = array(
								'pos'    => 0,
								'msg'    => $name . ' limit exceeded. Only the first %s matches will be processed',
								'params' => array($config['limit'])
							);

							$skip = true;
						}
					}
				}

				if (!$isArray)
				{
					$matches = $matches[0];
				}
			}

			if (!isset($config['parser']))
			{
				$config['parser'] = array('self', 'get' . $name . 'Tags');
			}

			$ret = call_user_func($config['parser'], $text, $config, $matches);

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
					if (!isset($tag['params']))
					{
						$tag['params'] = array();
					}
					$tag['pass'] = $pass;
					$tags[]      = $tag;
				}
			}

			++$pass;
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

		$aliases = $this->passes['BBCode']['aliases'];
		$bbcodes = $this->passes['BBCode']['bbcodes'];

		/**
		* @var	array	Open BBCodes
		*/
		$bbcodeStack = array();

		/**
		* @var	array	List of allowed BBCode tags in current context. Starts as a copy of $aliases
		*/
		$allowed = $aliases;

		/**
		* @var	array	Number of times each BBCode has been used
		*/
		$cntTotal = array_fill_keys($allowed, 0);

		/**
		* @var	array	Number of open tags for each bbcode_id
		*/
		$cntOpen = $cntTotal;

		/**
		* @var	array	Keeps track open tags (tags carry their suffix)
		*/
		$openTags = array();

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

			$bbcodeId = $tag['name'];
			if (!isset($bbcodes[$bbcodeId]))
			{

				$bbcodeId = strtoupper($bbcodeId);

				if (!isset($aliases[$bbcodeId]))
				{
					$this->msgs['debug'][] = array(
						'pos'    => $tag['pos'],
						'msg'    => 'Unknown BBCode %1$s from pass %2$s',
						'params' => array($bbcodeId, $tag['pass'])
					);
					continue;
				}

				$bbcodeId = $aliases[$bbcodeId];
			}

			$bbcode = $bbcodes[$bbcodeId];
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
					$lastBBCode = end($bbcodeStack);
					foreach ($bbcode['close_parent'] as $parent)
					{
						if ($lastBBCode['bbcode_id'] === $parent)
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

				if ($bbcode['nesting_limit'] <= $cntOpen[$bbcodeId]
				 || $bbcode['tag_limit']     <= $cntTotal[$bbcodeId])
				{
					continue;
				}

				if (!isset($allowed[$bbcodeId]))
				{
					$this->msgs['debug'][] = array(
						'pos'    => $tag['pos'],
						'msg'    => 'BBCode %s is not allowed in this context',
						'params' => array($bbcodeId)
					);
					continue;
				}

				if (isset($bbcode['require_parent']))
				{
					$lastBBCode = end($bbcodeStack);

					if (!$lastBBCode
					 || $lastBBCode['bbcode_id'] !== $bbcode['require_parent'])
					{
						$this->msgs['debug'][] = array(
							'pos'    => $tag['pos'],
							'msg'    => 'BBCode %1$s requires %2$s as parent',
							'params' => array($bbcodeId, $bbcode['require_parent'])
						);

						continue;
					}
				}

				if (isset($bbcode['require_ascendant']))
				{
					foreach ($bbcode['require_ascendant'] as $ascendant)
					{
						if (empty($cntOpen[$ascendant]))
						{
							$this->msgs['debug'][] = array(
								'pos'    => $tag['pos'],
								'msg'    => 'BBCode %1$s requires %2$s as ascendant',
								'params' => array($bbcodeId, $ascendant)
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
					foreach (array_diff_key($bbcode['params'], $tag['params']) as $param => $paramConf)
					{
						if (empty($paramConf['is_required']))
						{
							continue;
						}

						$this->msgs['error'][] = array(
							'pos'    => $tag['pos'],
							'msg'    => 'Missing param %s',
							'params' => array($param)
						);

						continue 2;
					}

					$invalid = array();
					foreach ($tag['params'] as $k => &$v)
					{
						$msgs = array();
						$v    = $this->filter($v, $bbcode['params'][$k]['type'], $msgs);

						foreach ($msgs as $type => $_msgs)
						{
							foreach ($_msgs as $msg)
							{
								$msg['pos'] = $tag['pos'];
								$this->msgs[$type][] = $msg;
							}
						}

						if ($v === false)
						{
							$this->msgs['error'][] = array(
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
					if (empty($bbcode['trim_before']))
					{
						$xml->text(substr($text, $pos, $tag['pos'] - $pos));
					}
					else
					{
						$len     = $tag['pos'] - $pos;
						$content = rtrim(substr($text, $pos, $len));
						$xml->text($content);

						if (strlen($content) < $len)
						{
							$xml->writeElement(
								'i',
								substr(
									$text,
									$pos + strlen($content),
									$len - strlen($content)
								)
							);
						}
					}
				}
				$pos = $tag['pos'] + $tag['len'];

				++$cntTotal[$bbcodeId];

				$xml->startElement($bbcodeId);
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
						if (!empty($bbcode['ltrim_content']))
						{
							/**
							* @see http://docs.php.net/manual/en/function.ltrim.php
							*/
							$spn = strspn($text, " \n\r\t\0\x0B", $tag['pos'], $tag['len']);

							if ($spn)
							{
								$xml->writeElement('i', substr($text, $tag['pos'], $spn));
								$tag['pos'] += $spn;
								$tag['len'] -= $spn;
							}
						}

						if (!empty($bbcode['rtrim_content']))
						{
							$content = rtrim(substr($text, $tag['pos'], $tag['len']));
							$xml->text($content);

							if (strlen($content) < $tag['len'])
							{
								$xml->writeElement(
									'i',
									substr(
										$text,
										$tag['pos'] + strlen($content),
										$tag['len'] - strlen($content)
									)
								);
							}
						}
						else
						{
							$xml->text(substr($text, $tag['pos'], $tag['len']));
						}
					}

					$xml->endElement();

					if (!empty($bbcode['trim_after']))
					{
						$spn = strspn($text, " \n\r\t\0\x0B", $pos);

						if ($spn)
						{
							$xml->writeElement('i', substr($text, $pos, $spn));
							$pos += $spn;
						}
					}

					continue;
				}

				if ($tag['len'])
				{
					$xml->writeElement('st', substr($text, $tag['pos'], $tag['len']));
				}

				if (!empty($bbcode['ltrim_content']))
				{
					$spn = strspn($text, " \n\r\t\0\x0B", $pos);

					if ($spn)
					{
						$xml->writeElement('i', substr($text, $pos, $spn));
						$pos += $spn;
					}
				}

				++$cntOpen[$bbcodeId];

				if (isset($openTags[$bbcodeId . $suffix]))
				{
					++$openTags[$bbcodeId . $suffix];
				}
				else
				{
					$openTags[$bbcodeId . $suffix] = 1;
				}

				$bbcodeStack[] = array(
					'bbcode_id' => $bbcodeId,
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
				if (empty($openTags[$bbcodeId . $suffix]))
				{
					/**
					* This is an end tag but there's no matching start tag
					*/
					$this->msgs['debug'][] = array(
						'pos'    => $tag['pos'],
						'msg'    => 'Could not find a matching start tag for BBCode %s',
						'params' => array($bbcodeId . $suffix)
					);
					continue;
				}

				if ($tag['pos'] > $pos)
				{
					/**
					* There's text between last tag and current tag
					*/
					if (empty($bbcode['rtrim_content']))
					{
						$xml->text(substr($text, $pos, $tag['pos'] - $pos));
					}
					else
					{
						$content = rtrim(substr($text, $pos, $tag['pos'] - $pos));
						$xml->text($content);

						/**
						* Move $pos by the length of $content. If it does not catch up to current
						* tag's pos, it means there's some whitespace in-between, which we write
						* in a <i/> tag. "i" stands for "ignore"
						*
						* Alternatively, we could cram that whitespace into the <et/> tag, although
						* it is not as semantically sound
						*/
						$pos += strlen($content);

						if ($tag['pos'] > $pos)
						{
							$xml->writeElement('i', substr($text, $pos, $tag['pos'] - $pos));
						}
					}
				}

				$pos = $tag['pos'] + $tag['len'];

				do
				{
					$cur     = array_pop($bbcodeStack);
					$allowed = $cur['allowed'];

					--$cntOpen[$cur['bbcode_id']];
					--$openTags[$cur['bbcode_id'] . $cur['suffix']];

					if ($cur['bbcode_id'] === $bbcodeId)
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

				if (!empty($bbcode['trim_after']))
				{
					$spn = strspn($text, " \n\r\t\0\x0B", $pos);

					if ($spn)
					{
						$xml->writeElement('i', substr($text, $pos, $spn));
						$pos += $spn;
					}
				}
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

	public function filter($var, $type, array &$msgs = array())
	{
		if (isset($this->filters[$type]['callback']))
		{
			return call_user_func_array(
				$this->filters[$type]['callback'],
				array(
					$var,
					$this->filters[$type],
					&$msgs
				)
			);
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
					$msgs['error'][] = array(
						'msg'    => 'URL scheme %s is not allowed',
						'params' => array($p['scheme'])
					);
					return false;
				}

				if (isset($this->filters['url']['disallowed_hosts'])
				 && preg_match($this->filters['url']['disallowed_hosts'], $p['host']))
				{
					$msgs['error'][] = array(
						'msg'    => 'URL host %s is not allowed',
						'params' => array($p['host'])
					);
					return false;
				}

				/**
				* We escape quotes just in case someone would want to use the URL in some Javascript
				* thingy
				*/
				return str_replace(array("'", '"'), array('%27', '%22'), $var);

			case 'simpletext':
				return filter_var($var, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '#^[a-zA-Z0-9\\-+.,_ ]+$#D')
				));

			case 'text':
				return (string) $var;

			case 'email':
				return filter_var($var, \FILTER_VALIDATE_EMAIL);

			case 'int':
			case 'integer':
				return filter_var($var, \FILTER_VALIDATE_INT);

			case 'float':
				return filter_var($var, \FILTER_VALIDATE_FLOAT);

			case 'number':
			case 'uint':
				return filter_var($var, \FILTER_VALIDATE_INT, array(
					'options' => array('min_range' => 0)
				));

			case 'color':
				return filter_var($var, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '/^(?:#[0-9a-f]{3,6}|[a-z]+)$/Di')
				));

			default:
				$msgs['debug'][] = array(
					'msg'    => 'Unknown filter %s',
					'params' => array($type)
				);
				return false;
		}
	}

	//==========================================================================
	// Tokenizers
	//==========================================================================

	static public function getAutolinkTags($text, array $config, array $matches)
	{
		$tags = array();
		$msgs = array();

		$bbcode = $config['bbcode'];
		$param  = $config['param'];

		foreach ($matches as $m)
		{
			$url = $m[0][0];

			/**
			* Remove some trailing punctuation. We preserve right parentheses if there's a left
			* parenthesis in the URL, as in http://en.wikipedia.org/wiki/Mars_(disambiguation) 
			*/
			$url   = rtrim($url);
			$rtrim = (strpos($url, '(')) ? '.' : ').';
			$url   = rtrim($url, $rtrim);

			$tags[] = array(
				'pos'    => $m[0][1],
				'name'   => $bbcode,
				'type'   => self::TAG_OPEN,
				'len'    => 0,
				'params' => array($param => $url)
			);
			$tags[] = array(
				'pos'    => $m[0][1] + strlen($url),
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

	static public function getBBCodeTags($text, array $config, array $matches)
	{
		$tags = array();
		$msgs = array();

		$bbcodes = $config['bbcodes'];
		$aliases = $config['aliases'];
		$textLen = strlen($text);

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

			$bbcodeId = $aliases[$alias];
			$bbcode   = $bbcodes[$bbcodeId];
			$params   = array();

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
						'params' => array($bbcodeId)
					);
				}
				continue;
			}

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
				$type       = self::TAG_OPEN;
				$wellFormed = false;
				$param      = null;

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
						$param = strtolower($bbcodeId);

						$msgs['debug'][] = array(
							'pos'    => $rpos,
							'msg'    => "BBCode %s does not have a default param, using BBCode's name as param name",
							'params' => array($bbcodeId)
						);
					}

					++$rpos;
				}

				while ($rpos < $textLen)
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

							if ($rpos === $textLen)
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

						$wellFormed = true;
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

						if ($rpos + $spn >= $textLen)
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
						$valuePos = $rpos + 1;

						while (++$rpos < $textLen)
						{
							$rpos = strpos($text, $c, $rpos);

							if ($rpos === false)
							{
								/**
								* No matching quote, apparently that string never ends...
								*/
								$msgs['error'][] = array(
									'pos' => $valuePos - 1,
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

						$value = stripslashes(substr($text, $valuePos, $rpos - $valuePos));

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

				if (!$wellFormed)
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
					$pos = stripos($text, '[/' . $bbcodeId . $suffix . ']', $rpos);

					if ($pos)
					{
						$params[$bbcode['default_param']]
							= substr($text, 1 + $rpos, $pos - (1 + $rpos));
					}
				}
			}

			$tags[] = array(
				'name'   => $bbcodeId,
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

	static public function getCensorTags($text, array $config, array $matches)
	{
		$bbcode = $config['bbcode'];
		$param  = $config['param'];

		$cnt   = 0;
		$tags  = array();
		$msgs  = array();

		foreach ($matches as $k => $_matches)
		{
			$replacements = (isset($config['replacements'][$k])) ? $config['replacements'][$k] : array();

			foreach ($_matches as $m)
			{
				$tag = array(
					'pos'  => $m[0][1],
					'name' => $bbcode,
					'type' => self::TAG_SELF,
					'len'  => strlen($m[0][0])
				);

				foreach ($replacements as $mask => $replacement)
				{
					if (preg_match($mask, $m[0][0]))
					{
						$tag['params'][$param] = $replacement;
						break;
					}
				}

				$tags[] = $tag;
			}
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}

	static public function getEmoticonTags($text, array $config, array $matches)
	{
		$tags = array();
		$msgs = array();

		foreach ($matches as $m)
		{
			$tags[] = array(
				'pos'    => $m[0][1],
				'type'   => self::TAG_SELF,
				'name'   => $config['bbcode'],
				'len'    => strlen($m[0][0])
			);
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}
}