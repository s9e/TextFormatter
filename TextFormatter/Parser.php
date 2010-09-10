<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

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
	* Characters that are removed by the trim_* config directives
	* @link http://docs.php.net/manual/en/function.trim.php
	*/
	const TRIM_CHARLIST = " \n\r\t\0\x0B";

	//==============================================================================================
	// Application stuff
	//==============================================================================================

	/**
	* @var array  Logged messages, reinitialized whenever a text is parsed
	*/
	protected $log;

	/**
	* @var array  Formatting passes
	*/
	protected $passes;

	/**
	* @var array  Parameter filters
	*/
	protected $filters;

	//==============================================================================================
	// Per-formatting vars
	//==============================================================================================

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* @var array  Unprocessed tags, in reverse order
	*/
	protected $tagStack;

	/**
	* @var array  Processed tags, in document order
	*/
	protected $tags;

	public function __construct(array $config)
	{
		$this->passes  = $config['passes'];
		$this->filters = $config['filters'];
	}

	/**
	* Return the message log
	*
	* @return array
	*/
	public function getLog()
	{
		return $this->log;
	}

	public function clear()
	{
		$this->log      = array();
		$this->tagStack = array();
		$this->tags     = array();
		unset($this->text);
	}

	public function parse($text, $writer = '\\XMLWriter')
	{
		$this->clear();
		$this->text = $text;

		/**
		* Capture all tags
		*/
		$this->captureTags();

		/**
		* Normalize tag names and remove unknown tags
		*/
		$this->normalizeTags();

		/**
		* Add the info related to whitespace trimming. From the parser's perspective, whitespace
		* becomes part of the tag; therefore, leading whitespace changes the tag's position, which
		* is why we do this _before_ sorting the tags
		*/
		$this->addTrimmingInfo();

		/**
		* Sort them by position and precedence
		*/
		$this->sortTags();

		/**
		* Remove overlapping tags, filter invalid tags, apply BBCode rules and stuff
		*/
		$this->processTags();


		$xml = new $writer;
		$xml->openMemory();

		if (empty($this->tags))
		{
			$xml->writeElement('pt', $this->text);
			return trim($xml->outputMemory(true));
		}

		$xml->startElement('rt');
		$pos = 0;
		foreach ($this->tags as $tag)
		{
			$xml->text(substr($this->text, $pos, $tag['pos'] - $pos));

			/**
			* Capture the part of the text that belongs to this tag then move the cursor
			*/
			$text = substr($this->text, $tag['pos'], $tag['len']);
			$pos  = $tag['pos'] + $tag['len'];

			if (!empty($tag['trim_before']))
			{
				$xml->writeElement('i', substr($this->text, $pos, $tag['trim_before']));

				$text = substr($text, $tag['trim_before']);
			}

			if (!empty($tag['trim_after']))
			{
				$text = substr($text, 0, -$tag['trim_after']);
			}

			if ($tag['type'] & self::TAG_OPEN)
			{
				$xml->startElement($tag['name']);

				if (!empty($tag['params']))
				{
					foreach ($tag['params'] as $k => $v)
					{
						$xml->writeAttribute($k, $v);
					}
				}

				if ($text > '')
				{
					if ($tag['type'] & self::TAG_CLOSE)
					{
						$xml->text($text);
						$xml->endElement();
					}
					else
					{
						$xml->writeElement('st', $text);
					}
				}
			}
			else
			{
				if ($text > '')
				{
					$xml->writeElement('et', $text);
				}
				$xml->endElement();
			}

			if (!empty($tag['trim_after']))
			{
				$xml->writeElement('i', substr($this->text, $pos - $tag['trim_after'], $tag['trim_after']));
			}
		}

		if ($pos < strlen($this->text))
		{
			$xml->text(substr($this->text, $pos));
		}

		$xml->endDocument();

		return trim($xml->outputMemory(true));
	}

	/**
	* Normalize tag names and remove unknown tags
	*
	* @return void
	*/
	protected function normalizeTags()
	{
		$bbcodes = $this->passes['BBCode']['bbcodes'];
		$aliases = $this->passes['BBCode']['aliases'];

		foreach ($this->tagStack as $k => &$tag)
		{
			/**
			* Normalize the tag name
			*/
			if (!isset($bbcodes[$tag['name']]))
			{
				$bbcodeId = strtoupper($tag['name']);

				if (!isset($aliases[$bbcodeId]))
				{
					$this->log('debug', array(
						'pos'    => $tag['pos'],
						'msg'    => 'Removed unknown BBCode %1$s from pass %2$s',
						'params' => array($tag['name'], $tag['pass'])
					));

					unset($this->tagStack[$k]);
					continue;
				}

				$tag['name'] = $aliases[$bbcodeId];
			}

			/**
			* Sort params alphabetically. Can be useful if someone wants to process the
			* output using regexp
			*/
			ksort($tag['params']);
		}
	}

	/**
	* Add trimming info to tags
	*
	* For tags where one of the trim* directive is set, the "pos" and "len" attributes are adjusted
	* to comprise the surrounding whitespace and two attributes, "trim_before" and "trim_after" are
	* added.
	*
	* @todo rename config settings to trim_before_start, trim_after_start, trim_before_end, trim_after_end
	*
	* @return void
	*/
	protected function addTrimmingInfo()
	{
		$bbcodes = $this->passes['BBCode']['bbcodes'];

		$pos = 0;
		foreach ($this->tagStack as &$tag)
		{
			$bbcode = $bbcodes[$tag['name']];

			/**
			* Original: "  [b]  -text-  [/b]  "
			* Matches:  "XX[b]  -text-XX[/b]  "
			*/
			if (($tag['type']  &  self::TAG_OPEN  && !empty($bbcode['trim_before']))
			 || ($tag['type'] === self::TAG_CLOSE && !empty($bbcode['rtrim_content'])))
			{
				$tag['trim_before'] = strspn(strrev(substr($this->text, $pos, $tag['pos'] - $pos)), self::TRIM_CHARLIST);
				$tag['len']        += $tag['trim_before'];
				$tag['pos']        -= $tag['trim_before'];
			}

			/**
			* Move the cursor past the tag
			*/
			$pos = $tag['pos'] + $tag['len'];

			/**
			* Original: "  [b]  -text-  [/b]  "
			* Matches:  "  [b]XX-text-  [/b]XX"
			*/
			if (($tag['type'] === self::TAG_OPEN  && !empty($bbcode['ltrim_content']))
			 || ($tag['type']  &  self::TAG_CLOSE && !empty($bbcode['trim_after'])))
			{
				$tag['trim_after']  = strspn($this->text, self::TRIM_CHARLIST, $pos);
				$tag['len']        += $tag['trim_after'];
				$pos               += $tag['trim_after'];
			}
		}
	}

	/**
	* 
	*
	* @return void
	*/
	protected function processTags()
	{
		if (empty($this->tagStack))
		{
			return;
		}

		//======================================================================
		// Time to get serious
		//======================================================================

		$aliases = $this->passes['BBCode']['aliases'];
		$bbcodes = $this->passes['BBCode']['bbcodes'];

		/**
		* @var array Open BBCodes
		*/
		$bbcodeStack = array();

		/**
		* @var array List of allowed BBCode tags in current context. Starts as a copy of $aliases
		*/
		$allowed = $aliases;

		/**
		* @var array Number of times each BBCode has been used
		*/
		$cntTotal = array_fill_keys($allowed, 0);

		/**
		* @var array Number of open tags for each bbcode_id
		*/
		$cntOpen = $cntTotal;

		/**
		* @var array Keeps track open tags (tags carry their suffix)
		*/
		$openTags = array();

		$pos = 0;
		do
		{
			$tag = array_pop($this->tagStack);

			if ($pos > $tag['pos'])
			{
				$this->log('debug', array(
					'pos' => $tag['pos'],
					'msg' => 'Tag skipped'
				));
				continue;
			}

			$bbcodeId = $tag['name'];
			$bbcode   = $bbcodes[$bbcodeId];
			$suffix   = (isset($tag['suffix'])) ? $tag['suffix'] : '';

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
					foreach ($bbcode['close_parent'] as $parentBBCodeId)
					{
						if ($lastBBCode['bbcode_id'] === $parentBBCodeId)
						{
							/**
							* So we do have to close that parent. First we reinsert current tag then
							* we append a new closing tag for the parent.
							*/
							$this->tagStack[] = $tag;
							$this->tagStack[] = array(
								'pos'  => $tag['pos'],
								'name' => $parentBBCodeId,
								'len'  => 0,
								'type' => self::TAG_CLOSE
								/** @todo TEST ME
								'suffix' => $lastBBCode['suffix']
								*/
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
					$this->log('debug', array(
						'pos'    => $tag['pos'],
						'msg'    => 'BBCode %s is not allowed in this context',
						'params' => array($bbcodeId)
					));
					continue;
				}

				if (isset($bbcode['require_parent']))
				{
					$lastBBCode = end($bbcodeStack);

					if (!$lastBBCode
					 || $lastBBCode['bbcode_id'] !== $bbcode['require_parent'])
					{
						$this->log('debug', array(
							'pos'    => $tag['pos'],
							'msg'    => 'BBCode %1$s requires %2$s as parent',
							'params' => array($bbcodeId, $bbcode['require_parent'])
						));

						continue;
					}
				}

				if (isset($bbcode['require_ascendant']))
				{
					foreach ($bbcode['require_ascendant'] as $ascendant)
					{
						if (empty($cntOpen[$ascendant]))
						{
							$this->log('debug', array(
								'pos'    => $tag['pos'],
								'msg'    => 'BBCode %1$s requires %2$s as ascendant',
								'params' => array($bbcodeId, $ascendant)
							));
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

						$this->log('error', array(
							'pos'    => $tag['pos'],
							'msg'    => 'Missing param %s',
							'params' => array($param)
						));

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
								$this->log($type, $msg);
							}
						}

						if ($v === false)
						{
							$this->log('error', array(
								'pos'    => $tag['pos'],
								'msg'    => 'Invalid param %s',
								'params' => array($k)
							));

							if ($bbcode['params'][$k]['is_required'])
							{
								// Skip this tag
								continue 2;
							}

							unset($tag['params'][$k]);
						}
					}
				}

				//==============================================================
				// Ok, so we have a valid BBCode
				//==============================================================

				$this->tags[] = $tag;

				$pos = $tag['pos'] + $tag['len'];

				++$cntTotal[$bbcodeId];

				if ($tag['type'] & self::TAG_CLOSE)
				{
					continue;
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
					$this->log('debug', array(
						'pos'    => $tag['pos'],
						'msg'    => 'Could not find a matching start tag for BBCode %s',
						'params' => array($bbcodeId . $suffix)
					));
					continue;
				}

				$pos = $tag['pos'] + $tag['len'];

				do
				{
					$cur     = array_pop($bbcodeStack);
					$allowed = $cur['allowed'];

					--$cntOpen[$cur['bbcode_id']];
					--$openTags[$cur['bbcode_id'] . $cur['suffix']];

					if ($cur['bbcode_id'] !== $bbcodeId)
					{
						$this->tags[] = array(
							'name' => $cur['bbcode_id'],
							'pos'  => $tag['pos'],
							'len'  => 0,
							'type' => self::TAG_CLOSE
						);
					}
					break;
				}
				while (1);

				$this->tags[] = $tag;
			}
		}
		while (!empty($this->tagStack));
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
								'pos' => $rpos,
								'msg' => 'Param name seems to extend till the end of $text'
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

	//==========================================================================
	// Internal stuff
	//==========================================================================

	/**
	* Add a message to the error log
	*
	* @param  string $type  Message type: debug, warning or error
	* @param  array  $entry Log info
	* @return void
	*/
	protected function log($type, array $entry)
	{
		$this->log[$type][] = $entry;
	}

	/**
	* Capture all the tags that apply to given text, sorted by precedence
	*
	* @return void
	*/
	protected function captureTags()
	{
		$this->tagStack = array();

		$pass = 0;
		foreach ($this->passes as $name => $config)
		{
			$matches = array();
			if (isset($config['regexp']))
			{
				/**
				* Some passes have several regexps in an array, others have a single regexp as a
				* string. We convert the latter to an array so that we can iterate over it.
				*/
				$isArray = is_array($config['regexp']);
				$regexps = ($isArray) ? $config['regexp'] : array($config['regexp']);

				/**
				* @var bool If true, skip the rest of the regexps
				*/
				$skip = false;

				$cnt = 0;
				foreach ($regexps as $k => $regexp)
				{
					$matches[$k] = array();

					if ($skip)
					{
						continue;
					}

					$_cnt = preg_match_all($regexp, $this->text, $matches[$k], \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);

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

							$this->log($msgType, array(
								'msg'    => $name . ' limit exceeded. Only the first %s matches will be processed',
								'params' => array($config['limit'])
							));

							$skip = true;
						}
					}
				}

				if (!$cnt)
				{
					/**
					* No matches? skip this pass
					*/
					continue;
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

			$ret = call_user_func($config['parser'], $this->text, $config, $matches);

			if (!empty($ret['msgs']))
			{
				foreach ($ret['msgs'] as $type => $msgs)
				{
					foreach ($msgs as $msg)
					{
						$this->log($type, $msg);
					}
				}
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

					$tag['pass']  = $pass;
					$this->tagStack[] = $tag;
				}
			}

			++$pass;
		}
	}

	/**
	* Sort tags by position and precedence
	*
	* @return void
	*/
	protected function sortTags()
	{
		/**
		* Sort by pos descending, tag type ascending (OPEN, CLOSE, SELF), pass descending
		*/
		usort($this->tagStack, function($a, $b)
		{
			return ($b['pos'] - $a['pos'])
			    ?: ($a['type'] - $b['type'])
			    ?: ($b['pass'] - $a['pass']);
		});
	}
}