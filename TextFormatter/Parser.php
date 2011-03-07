<?php

/**
* @package   s9e\Toolkit
* @copyright Copyright (c) 2010 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\Toolkit\TextFormatter;

use RuntimeException,
    XMLWriter;

class Parser
{
	/**
	* Start tag, e.g. [b]
	* -- becomes <B><st>[b]</st>
	*/
	const START_TAG  = 1;

	/**
	* End tag, e.g. [/b]
	* -- becomes <et>[/b]</et></B>
	*/
	const END_TAG = 2;

	/**
	* Self-closing tag, e.g. [img="http://..." /]
	* -- becomes <IMG>[img="http://..." /]</IMG>
	*
	* NOTE: SELF_CLOSING_TAG = START_TAG | END_TAG
	*/
	const SELF_CLOSING_TAG  = 3;

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
	* @var integer Cursor position during parsing
	*/
	protected $pos;

	/**
	* @var array  Unprocessed tags, in reverse order
	*/
	protected $tagStack;

	/**
	* @var array  Processed tags, in document order
	*/
	protected $tags;

	/**
	* @var array  Tag currently being processed, used in processTags()
	*/
	protected $currentTag;

	/**
	* @var string Name of the param currently being validated, used in processTags()
	*/
	protected $currentParam;

	//==============================================================================================
	// Public stuff
	//==============================================================================================

	/**
	* Constructor
	*
	* @param  array $config The config array returned by ConfigBuilder->getParserConfig()
	* @return void
	*/
	public function __construct(array $config)
	{
		$this->passes  = $config['passes'];
		$this->filters = $config['filters'];
	}

	/**
	* Return the message log
	*
	* @return array 2D array, first dimension is the message type: "debug", "warning" or "error"
	*/
	public function getLog()
	{
		return $this->log;
	}

	/**
	* Clear this instance's properties
	*
	* Used internally at the beginning of a new parsing. I suppose some memory-obsessive users will
	* appreciate to be able to do it whenever they feel like it
	*
	* @return void
	*/
	public function clear()
	{
		$this->log      = array();
		$this->tagStack = array();
		$this->tags     = array();

		unset($this->text, $this->currentTag, $this->currentParam);
	}

	/**
	* Parse given text, return the default (XML) representation
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		$this->clear();
		$this->text = $text;
		$this->prepareTags();

		return $this->output();
	}

	/**
	* Add a message to the error log
	*
	* @param  string $type  Message type: debug, warning or error
	* @param  array  $entry Log info
	* @return void
	*/
	public function log($type, array $entry)
	{
		if (isset($this->currentTag))
		{
			$entry['bbcodeId'] = $this->currentTag['name'];

			if (isset($this->currentParam))
			{
				$entry['paramName'] = $this->currentParam;
			}

			if (!isset($entry['pos']))
			{
				$entry['pos'] = $this->currentTag['pos'];
			}
		}

		$this->log[$type][] = $entry;
	}

	/**
	* Filter a var according to the configuration's filters
	*
	* Used internally but made public so that developers can test in advance whether a var would be
	* invalid. It mostly relies on PHP's own ext/filter extension but individual filters can be
	* overwritten by the config
	*
	* @param  mixed  $paramVal  Param value to be filtered/sanitized
	* @param  array  $paramConf Param configuration
	* @return mixed             The sanitized value of this param, or false if it was invalid
	*/
	public function filter($paramVal, array $paramConf)
	{
		$paramType = $paramConf['type'];

		if (isset($this->filters[$paramType]['callback']))
		{
			if (isset($this->filters[$paramType]['conf']))
			{
				/**
				* Add the filter's config to the param
				* NOTE: it doesn't overwrite the param's existing config
				*/
				$paramConf += $this->filters[$paramType]['conf'];
			}

			return call_user_func(
				$this->filters[$paramType]['callback'],
				$paramVal,
				$paramConf
			);
		}

		switch ($paramType)
		{
			case 'url':
				$paramVal = filter_var($paramVal, \FILTER_VALIDATE_URL);

				if (!$paramVal)
				{
					return false;
				}

				$p = parse_url($paramVal);

				if (!preg_match($this->filters['url']['allowed_schemes'], $p['scheme']))
				{
					$this->log('error', array(
						'msg'    => 'URL scheme %s is not allowed',
						'params' => array($p['scheme'])
					));
					return false;
				}

				if (isset($this->filters['url']['disallowed_hosts'])
				 && preg_match($this->filters['url']['disallowed_hosts'], $p['host']))
				{
					$this->log('error', array(
						'msg'    => 'URL host %s is not allowed',
						'params' => array($p['host'])
					));
					return false;
				}

				/**
				* We URL-encode quotes just in case someone would want to use the URL in some
				* Javascript thingy
				*/
				return str_replace(array("'", '"'), array('%27', '%22'), $paramVal);

			case 'identifier':
			case 'id':
				return filter_var($paramVal, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '#^[a-zA-Z0-9-_]+$#D')
				));

			case 'simpletext':
				return filter_var($paramVal, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '#^[a-zA-Z0-9\\-+.,_ ]+$#D')
				));

			case 'text':
				return (string) $paramVal;

			case 'email':
				return filter_var($paramVal, \FILTER_VALIDATE_EMAIL);

			case 'int':
			case 'integer':
				return filter_var($paramVal, \FILTER_VALIDATE_INT);

			case 'float':
				return filter_var($paramVal, \FILTER_VALIDATE_FLOAT);

			case 'number':
			case 'uint':
				return filter_var($paramVal, \FILTER_VALIDATE_INT, array(
					'options' => array('min_range' => 0)
				));

			case 'range':
				$paramVal = filter_var($paramVal, \FILTER_VALIDATE_INT);

				if ($paramVal === false)
				{
					return false;
				}

				if ($paramVal < $paramConf['min'])
				{
					$this->log('info', array(
						'msg'    => 'Minimum range value adjusted to %s',
						'params' => array($paramConf['min'])
					));
					return $paramConf['min'];
				}

				if ($paramVal > $paramConf['max'])
				{
					$this->log('info', array(
						'msg'    => 'Maximum range value adjusted to %s',
						'params' => array($paramConf['max'])
					));
					return $paramConf['max'];
				}

				return $paramVal;


			case 'color':
				return filter_var($paramVal, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '/^(?:#[0-9a-f]{3,6}|[a-z]+)$/Di')
				));

			case 'regexp':
				if (!preg_match($paramConf['regexp'], $paramVal, $match))
				{
					return false;
				}

				if (isset($paramConf['replace']))
				{
					/**
					* Even numbers of backslashes are replaced by half their number of backslashes.
					* A dollar sign followed by a number is replaced by the corresponding $match,
					* unless it's preceded by a backslash.
					*/
					return preg_replace_callback(
						'#(?:\\\\\\\\)+|(\\\\)?\\$([0-9]+)#',
						function($m) use ($match)
						{
							if (!isset($m[2]))
							{
								return stripslashes($m[0]);
							}

							return ($m[1]) ? '$' . $m[2] : $match[$m[2]];
						},
						$paramConf['replace']
					);
				}

				return $paramVal;

			default:
				$this->log('debug', array(
					'msg'    => 'Unknown filter %s',
					'params' => array($paramType)
				));
				return false;
		}
	}

	//==============================================================================================
	// The big cheese
	//==============================================================================================

	/**
	* Capture tabs and process them
	*
	* That's the main loop. It execute all the passes to capture this text's tags, clean them up
	* then apply rules and stuff
	*
	* @return void
	*/
	protected function prepareTags()
	{
		/**
		* Capture all tags
		*/
		$this->executePasses();

		/**
		* Normalize tag names and remove unknown tags
		*/
		$this->normalizeTags();

		/**
		* Sort them by position and precedence
		*/
		$this->sortTags();

		/**
		* Remove overlapping tags, filter invalid tags, apply BBCode rules and stuff
		*/
		$this->processTags();
	}

	/**
	* Default output format
	*
	* The purpose of this method is to be overwritten by child classes that want to output the
	* parsed text in their own format
	*
	* @return string XML representation of the parsed text
	*/
	protected function output()
	{
		return $this->asXML();
	}

	/**
	* Generate a XML representation of the text after parsing has completed
	*
	* @return string
	*/
	protected function asXML()
	{
		$xml = new XMLWriter;
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

			if ($tag['type'] & self::START_TAG)
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
					if ($tag['type'] & self::END_TAG)
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


	//==========================================================================
	// Internal stuff
	//==========================================================================

	/**
	* Append a tag to the list of processed tags
	*
	* @param  array $tag
	* @return void
	*/
	protected function appendTag(array $tag)
	{
		$offset = 0;

		if (!empty($this->tags))
		{
			/**
			* The left boundary is right after the last tag
			*/
			$lastTag = end($this->tags);
			$offset  = $lastTag['pos'] + $lastTag['len'];
		}

		/**
		* Add the info related to whitespace trimming. We have to do that here for several reasons:
		*
		*  1. We have to account for tags that are automatically closed (e.g. close_parent)
		*  2. If we do that before sorting the tags, there are some cases where multiple tags would
		*     attempt to claim the same whitespace
		*  3. If we do that after the sort, the order may become incorrect since leading whitespace
		*     becomes part of the tag, therefore changing its position
		*/
		$this->addTrimmingInfoToTag($tag, $offset);

		$this->tags[] = $tag;
	}

	/**
	* Add trimming info to a tag
	*
	* For tags where one of the trim* directive is set, the "pos" and "len" attributes are adjusted
	* to comprise the surrounding whitespace and two attributes, "trim_before" and "trim_after" are
	* added.
	*
	* Note that whitespace that is part of what a pass defines as a tag is left untouched.
	*
	* @param  array &$tag    Tag to which we add trimming info
	* @param  int    $offset Leftmost boundary when looking for whitespace before a tag
	* @return void
	*/
	protected function addTrimmingInfoToTag(array &$tag, $offset)
	{
		$bbcode = $this->passes['BBCode']['bbcodes'][$tag['name']];

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "XX[b]  -text-XX[/b]  "
		*/
		if (($tag['type']  &  self::START_TAG && !empty($bbcode['trim_before']))
		 || ($tag['type'] === self::END_TAG   && !empty($bbcode['rtrim_content'])))
		{
			$tag['trim_before'] = strspn(strrev(substr($this->text, $offset, $tag['pos'] - $offset)), self::TRIM_CHARLIST);
			$tag['len']        += $tag['trim_before'];
			$tag['pos']        -= $tag['trim_before'];
		}

		/**
		* Move the cursor past the tag
		*/
		$offset = $tag['pos'] + $tag['len'];

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "  [b]XX-text-  [/b]XX"
		*/
		if (($tag['type'] === self::START_TAG && !empty($bbcode['ltrim_content']))
		 || ($tag['type']  &  self::END_TAG   && !empty($bbcode['trim_after'])))
		{
			$tag['trim_after']  = strspn($this->text, self::TRIM_CHARLIST, $offset);
			$tag['len']        += $tag['trim_after'];
		}
	}

	/**
	* Execute all the passes and store their tags/messages
	*
	* @return void
	*/
	protected function executePasses()
	{
		$this->tagStack = array();

		$pass = 0;
		foreach ($this->passes as $passName => $passConfig)
		{
			$matches = array();
			if (isset($passConfig['regexp']))
			{
				/**
				* Some passes have several regexps in an array, others have a single regexp as a
				* string. We convert the latter to an array so that we can iterate over it.
				*/
				$isArray = is_array($passConfig['regexp']);
				$regexps = ($isArray) ? $passConfig['regexp'] : array($passConfig['regexp']);

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

					if (!empty($passConfig['limit'])
					 && $cnt > $passConfig['limit'])
					{
						if ($passConfig['limit_action'] === 'abort')
						{
							throw new RuntimeException($passName . ' limit exceeded');
						}
						else
						{
							$limit       = $passConfig['limit'] + $_cnt - $cnt;
							$msgType     = ($passConfig['limit_action'] === 'ignore') ? 'debug' : 'warning';
							$matches[$k] = array_slice($matches[$k], 0, $limit);

							$this->log($msgType, array(
								'msg'    => $passName . ' limit exceeded. Only the first %s matches will be processed',
								'params' => array($passConfig['limit'])
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

			if (!isset($passConfig['parser']))
			{
				$passConfig['parser'] = array('self', 'get' . $passName . 'Tags');
			}

			$ret = call_user_func($passConfig['parser'], $this->text, $passConfig, $matches);

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
						$tag['suffix'] = '-' . $passName;
					}

					if (!isset($tag['params']))
					{
						$tag['params'] = array();
					}

					$tag['pass']      = $pass;
					$this->tagStack[] = $tag;
				}
			}

			++$pass;
		}
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
		}
	}

	/**
	* Process the captured tags
	*
	* Removes overlapping tags, filter tags with invalid params, tags used in illegal places,
	* applies rules
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
		* @var array Keeps track of open tags (tags carry their suffix)
		*/
		$openTags = array();

		$pos = 0;
		do
		{
			$this->currentTag = array_pop($this->tagStack);

			if ($pos > $this->currentTag['pos'])
			{
				$this->log('debug', array(
					'pos' => $this->currentTag['pos'],
					'msg' => 'Tag skipped'
				));
				continue;
			}

			$bbcodeId = $this->currentTag['name'];
			$bbcode   = $bbcodes[$bbcodeId];
			$suffix   = (isset($this->currentTag['suffix'])) ? $this->currentTag['suffix'] : '';

			//==================================================================
			// Start tag
			//==================================================================

			if ($this->currentTag['type'] & self::START_TAG)
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
							* So we do have to close that parent. First we reinsert current tag... 
							*/
							$this->tagStack[] = $this->currentTag;

							/**
							* ...then we create a new end tag which we put on top of the stack
							*/
							$this->currentTag = array(
								'pos'    => $this->currentTag['pos'],
								'name'   => $parentBBCodeId,
								'suffix' => $lastBBCode['suffix'],
								'len'    => 0,
								'type'   => self::END_TAG
							);

							$this->addTrimmingInfoToTag($this->currentTag, $pos);
							$this->tagStack[] = $this->currentTag;

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
						'pos'    => $this->currentTag['pos'],
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
						$this->log('error', array(
							'pos'    => $this->currentTag['pos'],
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
								'pos'    => $this->currentTag['pos'],
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
					* Add default values
					*/
					$missingParams = array_diff_key($bbcode['params'], $this->currentTag['params']);

					foreach ($missingParams as $paramName => $paramConf)
					{
						if (isset($paramConf['default']))
						{
							$this->currentTag['params'][$paramName] = $paramConf['default'];
						}
					}

					/**
					* BBCode-level pre-filter
					*/
					if (isset($bbcode['pre_filter']))
					{
						foreach ($bbcode['pre_filter'] as $callback)
						{
							$this->currentTag['params'] =
								call_user_func($callback, $this->currentTag['params']);
						}
					}

					/**
					* Filter each param
					*/
					foreach ($this->currentTag['params'] as $paramName => &$paramVal)
					{
						$this->currentParam = $paramName;

						$paramConf   = $bbcode['params'][$paramName];
						$filteredVal = $paramVal;

						// execute pre-filter callbacks
						if (!empty($paramConf['pre_filter']))
						{
							foreach ($paramConf['pre_filter'] as $callback)
							{
								$filteredVal = call_user_func($callback, $filteredVal);
							}
						}

						// filter the value
						$filteredVal = $this->filter($filteredVal, $paramConf);

						// execute post-filter callbacks if the value was valid
						if ($filteredVal !== false
						 && !empty($paramConf['post_filter']))
						{
							foreach ($paramConf['post_filter'] as $callback)
							{
								$filteredVal = call_user_func($callback, $filteredVal);
							}
						}

						if ($filteredVal === false)
						{
							/**
							* Bad param value
							*/
							$this->log('error', array(
								'pos'    => $this->currentTag['pos'],
								'msg'    => 'Invalid param %s',
								'params' => array($paramName)
							));

							if (isset($paramConf['default']))
							{
								/**
								* Use the default value
								*/
								$filteredVal = $paramConf['default'];

								$this->log('debug', array(
									'pos'    => $this->currentTag['pos'],
									'msg'    => 'Using default value %1$s for param %2$s',
									'params' => array($paramConf['default'], $paramName)
								));
							}
							else
							{
								/**
								* Remove the param altogether
								*/
								unset($this->currentTag['params'][$paramName]);

								if ($paramConf['is_required'])
								{
									continue;
								}
							}
						}
						elseif ((string) $filteredVal !== (string) $paramVal)
						{
							$this->log('debug', array(
								'pos'    => $this->currentTag['pos'],
								'msg'    => 'Param value was altered by the filter '
								          . '(paramName: $1%s, paramVal: $2%s, filteredVal: $3%s)',
								'params' => array($paramName, $paramVal, $filteredVal)
							));
						}

						$paramVal = (string) $filteredVal;
					}
					unset($paramVal, $this->currentParam);

					/**
					* BBCode-level post-filter
					*/
					if (isset($bbcode['post_filter']))
					{
						foreach ($bbcode['post_filter'] as $callback)
						{
							$this->currentTag['params'] =
								call_user_func($callback, $this->currentTag['params']);
						}
					}

					/**
					* Check for missing required params
					*/
					$missingParams = array_diff_key($bbcode['params'], $this->currentTag['params']);

					foreach ($missingParams as $paramName => $paramConf)
					{
						if (empty($paramConf['is_required']))
						{
							continue;
						}

						$this->log('error', array(
							'pos'    => $this->currentTag['pos'],
							'msg'    => 'Missing param %s',
							'params' => array($paramName)
						));

						continue 2;
					}

					/**
					* Sort params alphabetically. Can be useful if someone wants to process the
					* output using regexps
					*/
					ksort($this->currentTag['params']);
				}

				//==============================================================
				// Ok, so we have a valid BBCode
				//==============================================================

				$this->appendTag($this->currentTag);

				$pos = $this->currentTag['pos'] + $this->currentTag['len'];

				++$cntTotal[$bbcodeId];

				if ($this->currentTag['type'] & self::END_TAG)
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

			if ($this->currentTag['type'] & self::END_TAG)
			{
				if (empty($openTags[$bbcodeId . $suffix]))
				{
					/**
					* This is an end tag but there's no matching start tag
					*/
					$this->log('debug', array(
						'pos'    => $this->currentTag['pos'],
						'msg'    => 'Could not find a matching start tag for BBCode %s',
						'params' => array($bbcodeId . $suffix)
					));
					continue;
				}

				$pos = $this->currentTag['pos'] + $this->currentTag['len'];

				do
				{
					$cur     = array_pop($bbcodeStack);
					$allowed = $cur['allowed'];

					--$cntOpen[$cur['bbcode_id']];
					--$openTags[$cur['bbcode_id'] . $cur['suffix']];

					if ($cur['bbcode_id'] !== $bbcodeId)
					{
						$this->appendTag(array(
							'name' => $cur['bbcode_id'],
							'pos'  => $this->currentTag['pos'],
							'len'  => 0,
							'type' => self::END_TAG
						));
					}
					break;
				}
				while (1);

				$this->appendTag($this->currentTag);
			}
		}
		while (!empty($this->tagStack));
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

	//==========================================================================
	// Tokenizers
	//==========================================================================

	/**@+
	* Capture tags
	*
	* Tokenizers share the same signature. They don't need to be part of the Parser.
	* If this pass's config contains one or more regexps, the matches are passed as the third
	* parameter. Tokenizers must return an array with up to two elements: "tags" which contains
	* the captured tags, and "msgs" which contains messages to be logged
	*
	* @param  string $text    Text to parse
	* @param  array  $config  This pass's config, as generated by ConfigBuilder
	* @param  array  $matches Regexp's matches, if applicable
	* @return array           2D array
	*/
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
				'type'   => self::START_TAG,
				'len'    => 0,
				'params' => array($param => $url)
			);
			$tags[] = array(
				'pos'    => $m[0][1] + strlen($url),
				'name'   => $bbcode,
				'type'   => self::END_TAG,
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

				$type = self::END_TAG;
			}
			else
			{
				$type       = self::START_TAG;
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
							$type = self::SELF_CLOSING_TAG;
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
						$spn = strspn($text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ', $rpos);

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

				$usesContent = false;

				if ($type === self::START_TAG
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

						$usesContent = true;
					}
				}
			}

			if ($type === self::START_TAG
			 && !$usesContent
			 && !empty($bbcode['auto_close']))
			{
				$endTag = '[/' . $bbcodeId . $suffix . ']';

				/**
				* Make sure that the start tag isn't immediately followed by an endtag
				*/
				if (strtoupper(substr($text, 1 + $rpos, strlen($endTag))) !== $endTag)
				{
					$type |= self::END_TAG;
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
					'type' => self::SELF_CLOSING_TAG,
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
				'type'   => self::SELF_CLOSING_TAG,
				'name'   => $config['bbcode'],
				'len'    => strlen($m[0][0])
			);
		}

		return array(
			'tags' => $tags,
			'msgs' => $msgs
		);
	}
	/**@- */
}