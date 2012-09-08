<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use RuntimeException;
use XMLWriter;

class Parser
{
	/**
	* Start tag, e.g. [b]
	* -- becomes <B><st>[b]</st>
	*/
	const START_TAG = 1;

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
	const SELF_CLOSING_TAG = 3;

	/**
	* Characters that are removed by the trim_* config directives
	* @link http://docs.php.net/manual/en/function.trim.php
	*/
	const TRIM_CHARLIST = " \n\r\t\0\x0B";

	//==============================================================================================
	// Application stuff
	//==============================================================================================

	/**
	* @var array Tags config
	*/
	protected $tagsConfig;

	/**
	* @var array Plugins config
	*/
	protected $pluginsConfig;

	/**
	* @var array Custom filters
	*/
	protected $filters = array();

	/**
	* @var array Registered namespaces: [prefix => uri]
	*/
	protected $registeredNamespaces = array();

	/**
	* @var array Context to be used for tags at the root of the document
	*/
	protected $rootContext;

	/**
	* @var array Array of PluginParser instances
	*/
	protected $pluginParsers = array();

	/**
	* @var array URL-specific config (disallowed hosts, allowed schemes, etc...)
	*/
	protected $urlConfig;

	//==============================================================================================
	// Per-formatting vars
	//==============================================================================================

	/**
	* @var array Logged messages, reinitialized whenever a text is parsed
	*/
	protected $log = array();

	/**
	* @var string  Text being parsed
	*/
	protected $text;

	/**
	* @var integer Length of the text being parsed
	*/
	protected $textLen;

	/**
	* @var array   Unprocessed tags, in reverse order
	*/
	protected $unprocessedTags;

	/**
	* @var array   Processed tags, in document order
	*/
	protected $processedTags;

	/**
	* @var array   The IDs of all the processed tags
	*/
	protected $processedTagIds;

	/**
	* @var array   Tags currently open, in document order
	*/
	protected $openTags;

	/**
	* @var array   Number of tags currently open, using the tag's tagMate value
	*/
	protected $openStartTags;

	/**
	* @var array   Number of open tags for each tag name
	*/
	protected $cntOpen;

	/**
	* @var array   Number of times each tag has been used
	*/
	protected $cntTotal;

	/**
	* @var array   Tag currently being processed, used in processTags()
	*/
	protected $currentTag;

	/**
	* @var string  Name of the attribute currently being validated, used in processTags()
	*/
	protected $currentAttribute;

	/**
	* @var array   Current context
	*/
	protected $context;

	/**
	* @var integer Current position in the text
	*/
	protected $pos;

	/**
	* @var bool    Whether we have seen any namespaced tags. Does not guarantee that any namespaced
	*              tags will appear in the result
	*/
	protected $hasNamespacedTags;

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
		$this->pluginsConfig = $config['plugins'];
		$this->tagsConfig    = $config['tags'];
		$this->urlConfig     = $config['urlConfig'];
		$this->rootContext   = $config['rootContext'];

		/**
		* @todo remove me
		*/
		foreach (array_keys($config['tags']) as $tagName)
		{
			$pos = strpos($tagName);

			if ($pos !== false)
			{
				$prefix = substr($tagName, 0, $pos);
				$config['namespaces'][$prefix] = 'urn:s9e:TextFormatter:' . $prefix;
			}
		}

		if (isset($config['namespaces']))
		{
			$this->registeredNamespaces = $config['namespaces'];
		}

		if (isset($config['filters']))
		{
			$this->filters = $config['filters'];
		}
	}

	/**
	* Return the tags' config
	*
	* @return array
	*/
	public function getTagsConfig()
	{
		return $this->tagsConfig;
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
	* Reset this instance's properties
	*
	* Used internally at the beginning of a new parsing. I suppose some memory-obsessive users will
	* appreciate to be able to do it whenever they feel like it
	*
	* @param  string New text to be parsed
	* @return void
	*/
	public function reset($text)
	{
		$this->text    = $text;
		$this->textLen = strlen($text);

		$this->log = array();
		$this->unprocessedTags = array();
		$this->processedTags   = array();
		$this->processedTagIds = array();
		$this->openTags        = array();
		$this->openStartTags   = array();
		$this->cntOpen         = array();
		$this->cntTotal        = array();

		$this->hasNamespacedTags = false;

		unset($this->currentTag, $this->currentAttribute);
	}

	/**
	* Parse given text, return the default (XML) representation
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		$this->reset($text);

		/**
		* Capture all tags
		*/
		$this->executePluginParsers();

		/**
		* Normalize tag names and remove unknown tags
		*/
		$this->normalizeUnprocessedTags();

		/**
		* Sort them by position and precedence
		*/
		$this->sortTags();

		/**
		* Remove overlapping tags, filter invalid tags, apply tag rules and stuff
		*/
		$this->processTags();

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
			$entry['tagName'] = $this->currentTag['name'];
			$entry['pluginName'] = $this->currentTag['pluginName'];

			if (isset($this->currentAttribute))
			{
				$entry['attrName'] = $this->currentAttribute;
			}

			if (!isset($entry['pos']))
			{
				$entry['pos'] = $this->currentTag['pos'];
				$entry['len'] = $this->currentTag['len'];
			}
		}

		$this->log[$type][] = $entry;
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
		$xml->startDocument('1.0', 'utf-8');

		if (empty($this->processedTags))
		{
			$xml->writeElement('pt', $this->text);
		}
		else
		{
			$xml->startElement('rt');

			/**
			* Declare all namespaces in the root node
			*/
			if ($this->hasNamespacedTags)
			{
				$declared = array();
				foreach ($this->processedTags as $tag)
				{
					$pos = strpos($tag['name'], ':');
					if ($pos !== false)
					{
						$prefix = substr($tag['name'], 0, $pos);

						if (!isset($declared[$prefix]))
						{
							$declared[$prefix] = 1;

							$xml->writeAttribute(
								'xmlns:' . $prefix,
								$this->registeredNamespaces[$prefix]
							);
						}
					}
				}
			}

			/**
			* @var integer Position that tracks how much of the text has been consumed so far
			*/
			$pos = 0;

			foreach ($this->processedTags as $tag)
			{
				/**
				* Append the text that's between last tag and this one
				*/
				$xml->text(substr($this->text, $pos, $tag['pos'] - $pos));

				/**
				* Capture the part of the text that belongs to this tag then move the cursor past
				* current tag
				*/
				$tagText = substr($this->text, $tag['pos'], $tag['len']);
				$pos     = $tag['pos'] + $tag['len'];

				$wsBefore = $wsAfter = false;

				if (!empty($tag['trimBefore']))
				{
					$wsBefore = substr($tagText, 0, $tag['trimBefore']);
					$tagText  = substr($tagText, $tag['trimBefore']);
				}

				if (!empty($tag['trimAfter']))
				{
					$wsAfter = substr($tagText, -$tag['trimAfter']);
					$tagText = substr($tagText, 0, -$tag['trimAfter']);
				}

				if ($wsBefore !== false)
				{
					$xml->writeElement('i', $wsBefore);
				}

				if ($tag['type'] & self::START_TAG)
				{
					$xml->startElement($tag['name']);

					if (!empty($tag['attrs']))
					{
						foreach ($tag['attrs'] as $k => $v)
						{
							$xml->writeAttribute($k, $v);
						}
					}

					if ($tag['type'] & self::END_TAG)
					{
						$xml->text($tagText);
						$xml->endElement();
					}
					elseif ($tagText > '')
					{
						$xml->writeElement('st', $tagText);
					}
				}
				else
				{
					if ($tagText > '')
					{
						$xml->writeElement('et', $tagText);
					}
					$xml->endElement();
				}

				if ($wsAfter !== false)
				{
					$xml->writeElement('i', $wsAfter);
				}
			}

			/**
			* Append the rest of the text, past the last tag
			*/
			if ($pos < $this->textLen)
			{
				$xml->text(substr($this->text, $pos));
			}
		}

		$xml->endDocument();

		/**
		* Flush the buffer/destroy the writer
		*/
		$xml = $xml->outputMemory(true);

		/**
		* Remove the XML prolog
		*/
		if ($xml[1] === '?')
		{
			$xml = substr($xml, strpos($xml, '<', 2));
		}

		return rtrim($xml);
	}

	/**
	* Append a tag to the list of processed tags
	*
	* Takes care of maintaining counters and updating the context
	*
	* @param  array $tag
	* @return void
	*/
	protected function appendTag(array $tag)
	{
		$this->processedTags[] = $tag;
		$this->processedTagIds[$tag['id']] = 1;

		$this->pos = $tag['pos'] + $tag['len'];

		/**
		* Maintain counters
		*/
		if ($tag['type'] & self::START_TAG)
		{
			++$this->cntTotal[$tag['name']];

			if ($tag['type'] === self::START_TAG)
			{
				++$this->cntOpen[$tag['name']];

				if (isset($this->openStartTags[$tag['tagMate']]))
				{
					++$this->openStartTags[$tag['tagMate']];
				}
				else
				{
					$this->openStartTags[$tag['tagMate']] = 1;
				}
			}
		}
		elseif ($tag['type'] & self::END_TAG)
		{
			--$this->cntOpen[$tag['name']];
			--$this->openStartTags[$tag['tagMate']];
		}

		/**
		* Update the context
		*/
		if ($tag['type'] === self::START_TAG)
		{
			$tagConfig = $this->tagsConfig[$tag['name']];

			$this->openTags[] = array(
				'name'       => $tag['name'],
				'pluginName' => $tag['pluginName'],
				'tagMate'    => $tag['tagMate'],
				'attrs'      => $tag['attrs'],
				'context'    => $this->context
			);

			if (empty($tagConfig['isTransparent']))
			{
				$this->context['allowedChildren'] = $tagConfig['allowedChildren'];
			}

			$this->context['allowedDescendants'] &= $tagConfig['allowedDescendants'];
			$this->context['allowedChildren']    &= $this->context['allowedDescendants'];
		}
	}

	/**
	* Add trimming info to a tag
	*
	* For tags where one of the trim* directive is set, the "pos" and "len" attributes are adjusted
	* to comprise the surrounding whitespace and two attributes, "trimBefore" and "trimAfter" are
	* added.
	*
	* Note that whitespace that is part of what a pass defines as a tag is left untouched.
	*
	* @param  array &$tag
	* @return void
	*/
	protected function addTrimmingInfoToTag(&$tag)
	{
		$tagConfig = $this->tagsConfig[$tag['name']];

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "XX[b]  -text-XX[/b]  "
		*/
		if (($tag['type']  &  self::START_TAG && !empty($tagConfig['trimBefore']))
		 || ($tag['type'] === self::END_TAG   && !empty($tagConfig['rtrimContent'])))
		{
			$spn = strspn(
				strrev(substr($this->text, 0, $tag['pos'])),
				self::TRIM_CHARLIST
			);

			$tag['trimBefore']  = $spn;
			$tag['len']        += $spn;
			$tag['pos']        -= $spn;
		}

		/**
		* Original: "  [b]  -text-  [/b]  "
		* Matches:  "  [b]XX-text-  [/b]XX"
		*/
		if (($tag['type'] === self::START_TAG && !empty($tagConfig['ltrimContent']))
		 || ($tag['type']  &  self::END_TAG   && !empty($tagConfig['trimAfter'])))
		{
			$spn = strspn(
				$this->text,
				self::TRIM_CHARLIST,
				$tag['pos'] + $tag['len']
			);

			$tag['trimAfter']  = $spn;
			$tag['len']       += $spn;
		}
	}

	/**
	* Execute a plugin's regexps and return the result
	*
	* Takes care of regexpLimit/regexpAction
	*
	* @param  string $pluginName
	* @return mixed              An array of matches, a 2D array of matches, FALSE if no regexp
	*                            produced any matches or NULL if there's no regexp for this plugin
	*/
	protected function executePluginRegexp($pluginName)
	{
		$pluginConfig = $this->pluginsConfig[$pluginName];

		/**
		* Some plugins have several regexps in an array, others have a single regexp as a
		* string. We convert the latter to an array so that we can iterate over it.
		*/
		$isArray = is_array($pluginConfig['regexp']);
		$regexps = ($isArray) ? $pluginConfig['regexp'] : array($pluginConfig['regexp']);

		/**
		* @var bool If true, skip the rest of the regexps
		*/
		$skip = false;

		$matches = array();
		$cnt = 0;

		foreach ($regexps as $k => $regexp)
		{
			$matches[$k] = array();

			if ($skip)
			{
				continue;
			}

			$_cnt = preg_match_all(
				$regexp,
				$this->text,
				$matches[$k],
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			if (!$_cnt)
			{
				continue;
			}

			$cnt += $_cnt;

			if ($cnt > $pluginConfig['regexpLimit'])
			{
				if ($pluginConfig['regexpLimitAction'] === 'abort')
				{
					throw new RuntimeException($pluginName . ' limit exceeded');
				}
				else
				{
					$limit   = $pluginConfig['regexpLimit'] + $_cnt - $cnt;
					$msgType = ($pluginConfig['regexpLimitAction'] === 'ignore')
							 ? 'debug'
							 : 'warning';

					$matches[$k] = array_slice($matches[$k], 0, $limit);

					$this->log($msgType, array(
						'msg' => '%1$s limit exceeded. Only the first %2$s matches will be processed',
						'params' => array($pluginName, $pluginConfig['regexpLimit'])
					));

					$skip = true;
				}
			}
		}

		if (!$cnt)
		{
			return false;
		}

		return ($isArray)
		     ? $matches
		     : $matches[0];
	}

	/**
	* Return a cached instance of a PluginParser
	*
	* @param  string $pluginName
	* @return PluginParser
	*/
	protected function getPluginParser($pluginName)
	{
		/**
		* Check whether an instance is ready, the class exists or if we have to load it
		*/
		if (!isset($this->pluginParsers[$pluginName]))
		{
			$pluginConfig = $this->pluginsConfig[$pluginName];

			if (!isset($pluginConfig['parserClassName']))
			{
				$pluginConfig['parserClassName'] =
					__NAMESPACE__ . '\\Plugins\\' . $pluginName . 'Parser';

				$pluginConfig['parserFilepath'] =
					__DIR__ . '/Plugins/' . $pluginName . 'Parser.php';
			}

			$useAutoload = !isset($pluginConfig['parserFilepath']);

			if (!class_exists($pluginConfig['parserClassName'], $useAutoload)
			 && isset($pluginConfig['parserFilepath']))
			{
				/**
				* Check for the PluginParser class
				*/
				if (!class_exists(__NAMESPACE__ . '\\PluginParser'))
				{
					include __DIR__ . '/PluginParser.php';
				}

				include $pluginConfig['parserFilepath'];
			}

			$className = $pluginConfig['parserClassName'];

			$this->pluginParsers[$pluginName] = new $className($this, $pluginConfig);
		}

		return $this->pluginParsers[$pluginName];
	}

	/**
	* Execute all the plugins and store their tags
	*
	* @return void
	*/
	protected function executePluginParsers()
	{
		$tagId = 0;

		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
		{
			$matches = array();

			if (isset($pluginConfig['regexp']))
			{
				$matches = $this->executePluginRegexp($pluginName);

				if ($matches === false)
				{
					continue;
				}
			}

			$tags = $this->getPluginParser($pluginName)->getTags($this->text, $matches);

			/**
			* First add an ID to every tag
			*/
			foreach ($tags as &$tag)
			{
				$tag['id'] = ++$tagId;
				$tag['pluginName'] = $pluginName;
			}
			unset($tag);

			/**
			* Now that all tags have a unique ID, deal with 'requires' then add them
			*/
			foreach ($tags as $tag)
			{
				if (isset($tag['requires']))
				{
					$requires = array();
					foreach ($tag['requires'] as $k)
					{
						$requires[$tags[$k]['id']] = 1;
					}

					$tag['requires'] = $requires;
				}

				$this->unprocessedTags[] = $tag;
			}
		}
	}

	/**
	* Normalize tag names, remove unknown tags and add trimming info
	*
	* @return void
	*/
	protected function normalizeUnprocessedTags()
	{
		foreach ($this->unprocessedTags as $k => &$tag)
		{
			/**
			* If the tag's name isn't prefixed, we change it to uppercase.
			*
			* NOTE: we don't bother checking if the tag name would be valid since we check for the
			*       tag's existence in $this->tagsConfig and only valid tags should be found there
			*/
			if (strpos($tag['name'], ':') === false)
			{
				$tag['name'] = strtoupper($tag['name']);
			}
			else
			{
				$this->hasNamespacedTags = true;
			}

			if (!isset($this->tagsConfig[$tag['name']]))
			{
				$this->log('debug', array(
					'pos'    => $tag['pos'],
					'len'    => $tag['len'],
					'msg'    => 'Removed unknown tag %1$s from plugin %2$s',
					'params' => array($tag['name'], $tag['pluginName'])
				));

				unset($this->unprocessedTags[$k]);
				continue;
			}

			/**
			* Cast 'pos' and 'len' to int
			*/
			$tag['pos'] = (int) $tag['pos'];
			$tag['len'] = (int) $tag['len'];

			/**
			* Some methods expect those keys to always be set
			*/
			$tag += array(
				'attrs'   => array(),
				'tagMate' => ''
			);

			$tag['tagMate'] = $tag['pluginName']
			                . '-' . $tag['name']
			                . '#' . $tag['tagMate'];

			/**
			* Add trimming info
			*/
			$this->addTrimmingInfoToTag($tag);
		}
	}

	/**
	* Process the captured tags
	*
	* Removes overlapping tags, filter tags with invalid attributes, tags used in illegal places,
	* applies rules
	*
	* @return void
	*/
	protected function processTags()
	{
		if (empty($this->unprocessedTags))
		{
			return;
		}

		/**
		* Set up the root context
		*/
		$this->context = $this->rootContext;

		/**
		* Seed the tag counters with 0 for each tag
		*/
		$this->cntTotal = array_fill_keys(array_keys($this->tagsConfig), 0);
		$this->cntOpen  = $this->cntTotal;

		/**
		* Reset the cursor
		*/
		$this->pos = 0;

		/**
		* Iterate over unprocessed tags
		*/
		while ($this->nextTag())
		{
			$this->processCurrentTag();
		}

		/**
		* Close tags that were left open
		*/
		while ($this->openTags)
		{
			$this->currentTag = $this->createEndTag(
				end($this->openTags),
				$this->textLen
			);
			$this->processCurrentTag();
		}
	}

	/**
	* Pop the top unprocessed tag, put it in $this->currentTag and return it
	*
	* @return array
	*/
	protected function nextTag()
	{
		return $this->currentTag = array_pop($this->unprocessedTags);
	}

	/**
	* Peek at the top unprocessed tag without touching current tag
	*
	* @return array|bool Next tag to be processed, or FALSE if there's none left
	*/
	protected function peekNextTag()
	{
		return end($this->unprocessedTags);
	}

	/**
	* Pop at the top unprocessed tag without touching current tag
	*
	* @return array|bool Popped tag, or FALSE if there's none left
	*/
	protected function popNextTag()
	{
		return array_pop($this->unprocessedTags);
	}

	/**
	* Process current tag
	*/
	protected function processCurrentTag()
	{
		/**
		* Try to be less greedy with whitespace before current tag if it would make it overlap
		* with previous tag
		*/
		if (!empty($this->currentTag['trimBefore'])
		 && $this->pos > $this->currentTag['pos'])
		{
			/**
			* This is how much the tags overlap
			*/
			$spn = $this->pos - $this->currentTag['pos'];

			if ($spn <= $this->currentTag['trimBefore'])
			{
				/**
				* All of the overlap is whitespace, therefore we can reduce it to make the tags fit
				*/
				$this->currentTag['pos']        += $spn;
				$this->currentTag['len']        -= $spn;
				$this->currentTag['trimBefore'] -= $spn;
			}
		}

		/**
		* Test whether the current tag overlaps with previous tag
		*/
		if ($this->pos > $this->currentTag['pos'])
		{
			$this->log('debug', array(
				'msg' => 'Tag skipped'
			));
			return;
		}

		if ($this->currentTagRequiresMissingTag())
		{
			$this->log('debug', array(
				'msg' => 'Tag skipped due to missing dependency'
			));
			return;
		}

		if ($this->currentTag['type'] & self::START_TAG)
		{
			$this->processCurrentStartTag();
		}
		else
		{
			$this->processCurrentEndTag();
		}
	}

	/**
	* Process current tag, which is a START_TAG
	*/
	protected function processCurrentStartTag()
	{
		$tagName   = $this->currentTag['name'];
		$tagConfig = $this->tagsConfig[$tagName];

		/**
		* 1. Check that this tag has not reached its global limit tagLimit
		* 2. Filter this tag's attributes and check for missing attributes
		* 3. Apply closeParent and closeAncestor rules
		* 4. Check for nestingLimit
		* 5. Apply requireParent and requireAncestor rules
		*
		* This order ensures that the tag is valid and within the set limits before we attempt to
		* close parents or ancestors. We need to close ancestors before we can check for nesting
		* limits, whether this tag is allowed within current context (the context may change
		* as ancestors are closed) or whether the required ancestors are still there (they might
		* have been closed by a rule.)
		*/
		if ($this->cntTotal[$tagName] >= $tagConfig['tagLimit']
		 || !$this->filterAttributes()
		 || $this->closeParent()
		 || $this->closeAncestor()
		 || $this->cntOpen[$tagName]  >= $tagConfig['nestingLimit']
		 || $this->requireParent()
		 || $this->requireAncestor()
		 || !$this->tagIsAllowed($tagName))
		{
			return;
		}

		/**
		* If this tag must remain empty and it's not a self-closing tag, we peek at the next
		* tag before turning our start tag into a self-closing tag
		*/
		if (!empty($tagConfig['isEmpty'])
		 && $this->currentTag['type'] === self::START_TAG)
		{
			$nextTag = $this->peekNextTag();

			if ($nextTag
			 && $nextTag['type'] === self::END_TAG
			 && $nextTag['tagMate'] === $this->currentTag['tagMate']
			 && $nextTag['pos'] === $this->currentTag['pos'] + $this->currentTag['len'])
			{
				/**
				* Next tag is a match to current tag, pop it out of the unprocessedTags stack and
				* consume its text
				*/
				$this->popNextTag();
				$this->currentTag['len'] += $nextTag['len'];
			}

			$this->currentTag['type'] = self::SELF_CLOSING_TAG;
		}

		/**
		* We have a valid tag, let's append it to the list of processed tags
		*/
		$this->appendTag($this->currentTag);
	}

	/**
	* Test whether current tag is allowed in current context
	*
	* @param  string $tagName
	* @return bool
	*/
	protected function tagIsAllowed($tagName)
	{
		$n = $this->tagsConfig[$tagName]['n'];

		return (bool) (ord($this->context['allowedChildren'][$n >> 8]) & (1 << ($n & 7)));
	}

	/**
	* Process current tag, which is a END_TAG
	*/
	protected function processCurrentEndTag()
	{
		if (empty($this->openStartTags[$this->currentTag['tagMate']]))
		{
			/**
			* This is an end tag but there's no matching start tag
			*/
			$this->log('debug', array(
				'msg'    => 'Could not find a matching start tag for %s',
				'params' => array($this->currentTag['tagMate'])
			));
			return;
		}

		/**
		* @var bool  If true, check for reopenChild rules
		*/
		$reopenChildren = true;

		/**
		* @var array List of tags to be reopened due to reopenChild rules
		*/
		$reopenTags = array();

		/**
		* Iterate through open tags, for each start tag we find that is not the tagMate of current
		* end tag, we create a corresponding end tag
		*/
		processLastOpenTag:
		{
			$lastOpenTag = array_pop($this->openTags);
			$this->context = $lastOpenTag['context'];

			if ($lastOpenTag['tagMate'] !== $this->currentTag['tagMate'])
			{
				$this->appendTag($this->createEndTag($lastOpenTag, $this->currentTag['pos']));

				// Do we check for reopenChild rules?
				if ($reopenChildren)
				{
					$tagConfig = $this->tagsConfig[$this->currentTag['name']];

					if (isset($tagConfig['rules']['reopenChild'][$lastOpenTag['name']]))
					{
						// Position the reopened tag after current tag
						$pos = $this->currentTag['pos'] + $this->currentTag['len'];

						// Test whether the tag would be out of bounds
						if ($pos < $this->textLen)
						{
							$reopenTags[] = $this->createStartTag($lastOpenTag, $pos);
						}
					}
					else
					{
						// This tag is not meant to be reopened. Consequently, we won't reopen any
						$reopenChildren = false;
					}
				}

				goto processLastOpenTag;
			}
		}

		$this->appendTag($this->currentTag);

		if ($reopenChildren)
		{
			foreach ($reopenTags as $tag)
			{
				$this->unprocessedTags[] = $tag;
			}
		}
	}

	/**
	* Create a START_TAG at given position matching given tag
	*
	* @param  array   $tag  Reference tag
	* @param  integer $pos  Created tag's position
	* @return array         Created tag
	*/
	protected function createStartTag(array $tag, $pos)
	{
		return $this->createMatchingTag($tag, $pos, self::START_TAG);
	}

	/**
	* Create an END_TAG at given position, for given START_TAG
	*
	* @param  array   $tag  Reference tag
	* @param  integer $pos  Created tag's position
	* @return array         Created tag
	*/
	protected function createEndTag(array $tag, $pos)
	{
		return $this->createMatchingTag($tag, $pos, self::END_TAG);
	}

	/**
	* Create a tag at given position matching given tag
	*
	* @param  array   $tag  Reference tag
	* @param  integer $pos  Created tag's position
	* @param  integer $type Created tag's type
	* @return array         Created tag
	*/
	protected function createMatchingTag(array $tag, $pos, $type)
	{
		$newTag = array(
			'id'     => -1,
			'name'   => $tag['name'],
			'pos'    => $pos,
			'len'    => 0,
			'type'   => $type,
			'attrs'  => ($type === self::START_TAG) ? $tag['attrs'] : array(),
			'tagMate'    => $tag['tagMate'],
			'pluginName' => $tag['pluginName']
		);

		$this->addTrimmingInfoToTag($newTag);

		return $newTag;
	}

	/**
	* Apply closeParent rules from current tag
	*
	* @return bool Whether a new tag has been added
	*/
	protected function closeParent()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (!empty($this->openTags)
		 && !empty($tagConfig['rules']['closeParent']))
		{
			$parentTag     = end($this->openTags);
			$parentTagName = $parentTag['name'];

			if (isset($tagConfig['rules']['closeParent'][$parentTagName]))
			{
				/**
				* We have to close that parent. First we reinsert current tag...
				*/
				$this->unprocessedTags[] = $this->currentTag;

				/**
				* ...then we create a new end tag which we put on top of the stack
				*/
				$this->currentTag = $this->createEndTag(
					$parentTag,
					$this->currentTag['pos']
				);

				$this->unprocessedTags[] = $this->currentTag;

				return true;
			}
		}

		return false;
	}

	/**
	* Apply closeAncestor rules from current tag
	*
	* @return bool Whether a new tag has been added
	*/
	protected function closeAncestor()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (!empty($tagConfig['rules']['closeAncestor']))
		{
			$i = count($this->openTags);

			while (--$i >= 0)
			{
				$ancestorTag     = $this->openTags[$i];
				$ancestorTagName = $ancestorTag['name'];

				if (isset($tagConfig['rules']['closeAncestor'][$ancestorTagName]))
				{
					/**
					* We have to close this ancestor. First we reinsert current tag...
					*/
					$this->unprocessedTags[] = $this->currentTag;

					/**
					* ...then we create a new end tag which we put on top of the stack
					*/
					$this->currentTag = $this->createEndTag(
						$ancestorTag,
						$this->currentTag['pos']
					);

					$this->unprocessedTags[] = $this->currentTag;

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply requireParent rules from current tag
	*
	* @return bool Whether current tag is invalid
	*/
	protected function requireParent()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (isset($tagConfig['rules']['requireParent']))
		{
			$parentTag = end($this->openTags);

			if (!$parentTag
			 || !isset($tagConfig['rules']['requireParent'][$parentTag['name']]))
			{
				$msg = (count($tagConfig['rules']['requireParent']) === 1)
					 ? 'Tag %1$s requires %2$s as parent'
					 : 'Tag %1$s requires as parent any of: %2$s';

				$this->log('error', array(
					'msg'    => $msg,
					'params' => array(
						$this->currentTag['name'],
						implode(', ', $tagConfig['rules']['requireParent'])
					)
				));

				return true;
			}
		}

		return false;
	}

	/**
	* Apply requireAncestor rules from current tag
	*
	* @return bool Whether current tag is invalid
	*/
	protected function requireAncestor()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (isset($tagConfig['rules']['requireAncestor']))
		{
			foreach ($tagConfig['rules']['requireAncestor'] as $ancestor)
			{
				if (!empty($this->cntOpen[$ancestor]))
				{
					return false;
				}
			}

			$msg = (count($tagConfig['rules']['requireAncestor']) === 1)
				 ? 'Tag %1$s requires %2$s as ancestor'
				 : 'Tag %1$s requires as ancestor any of: %2$s';

			$this->log('error', array(
				'msg'    => $msg,
				'params' => array(
					$this->currentTag['name'],
					implode(', ', $tagConfig['rules']['requireAncestor'])
				)
			));

			return true;
		}

		return false;
	}

	/**
	* Test whether the current tag requires another tag and that tag is missing
	*
	* @return bool TRUE if the tag's requirements were NOT fulfilled, FALSE otherwise
	*/
	protected function currentTagRequiresMissingTag()
	{
		if (isset($this->currentTag['requires']))
		{
			return (bool) array_diff_key(
				$this->currentTag['requires'],
				$this->processedTagIds
			);
		}

		return false;
	}

	/**
	* Sort tags by position and precedence
	*
	* @return void
	*/
	protected function sortTags()
	{
		usort($this->unprocessedTags, array(get_class($this), 'compareTags'));
	}

	/**
	* sortTags() callback
	*
	* Unprocessed tags are stored as a stack, so their order is LIFO. We sort tags by position
	* _descending_ so that they are processed in the order they appear in the text.
	*
	* @param  array   First tag to compare
	* @param  array   Second tag to compare
	* @return integer
	*/
	protected static function compareTags(array $a, array $b)
	{
		// First we order by pos descending
		if ($a['pos'] !== $b['pos'])
		{
			return $b['pos'] - $a['pos'];
		}

		if (!$a['len'] || !$b['len'])
		{
			// Zero-width end tags are ordered after zero-width start tags so that a pair that ends
			// with a zero-width tag has the opportunity to be closed before another pair starts
			// with a zero-width tag. For example, the pairs that would enclose the letters X and Y
			// in the string "XY". Self-closing tags are ordered between end tags and start tags in
			// an attempt to keep them out of tag pairs
			if (!$a['len'] && !$b['len'])
			{
				$order = array(
					self::END_TAG => 2,
					self::SELF_CLOSING_TAG => 1,
					self::START_TAG => 0
				);
				return $order[$a['type']] - $order[$b['type']];
			}

			// Here, we know that only one of $a or $b is a zero-width tags. Zero-width tags are
			// ordered after wider tags so that they have a chance to be processed before the next
			// character is consumed, which would force them to be skipped
			return ($a['len']) ? -1 : 1;
		}

		// Here we know that both tags start at the same position and have a length greater than 0.
		// We sort tags by length ascending, so that the longest matches are processed first
		if ($a['len'] !== $b['len'])
		{
			return ($a['len'] - $b['len']);
		}

		// Finally, if the tags start at the same position and are the same length, sort them by id
		// descending, which is our version of a stable sort (tags that were added first end up
		// being processed first)
		return $b['id'] - $a['id'];
	}

	/**
	* Filter attributes from current tag
	*
	* Will execute attribute parsers if applicable, then it will filter the attributes, replacing
	* invalid attributes with their default value or returning FALSE if a required attribute is
	* missing or invalid (and with no default value.)
	*
	* @return bool Whether the set of attributes is valid
	*/
	protected function filterAttributes()
	{
		// Handle parsable attributes
		$this->parseAttributes();

		// Save the current attribute values then reset current tag's attributes
		$attrVals = $this->currentTag['attrs'];
		$this->currentTag['attrs'] = array();

		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (empty($tagConfig['attrs']))
		{
			// No attributes defined
			return true;
		}

		foreach ($tagConfig['attrs'] as $attrName => $attrConf)
		{
			$this->currentAttribute = $attrName;

			// The initialize with an invalid value. If the attribute is missing, we treat it as if
			// it was invalid
			$attrVal = false;

			// If the attribute exists, filter it
			if (isset($attrVals[$attrName]))
			{
				$attrVal = $this->filterAttribute($attrVals[$attrName], $attrConf);

				if ($attrVal === false)
				{
					// The attribute is invalid
					$this->log('error', array(
						'msg'    => "Invalid attribute '%s'",
						'params' => array($attrName)
					));
				}
			}

			// If the attribute is missing or invalid...
			if ($attrVal === false)
			{
				if (isset($attrConf['defaultValue']))
				{
					// Use its default value
					$attrVal = $attrConf['defaultValue'];
				}
				elseif (!empty($attrConf['required']))
				{
					// No default value and the attribute is required... log it and bail
					$this->log('error', array(
						'msg'    => "Missing attribute '%s'",
						'params' => array($attrName)
					));

					return false;
				}
				else
				{
					// The attribute is invalid but it's not required so we move on to the next one
					continue;
				}
			}

			// We have a value for this attribute, we can add it back to the tag
			$this->currentTag['attrs'][$attrName] = $attrVal;
		}

		return true;
	}

	/**
	* Filter an attribute value according to given config
	*
	* @param  mixed $attrVal  Attribute value
	* @param  array $attrConf Attribute config
	* @return mixed           Filtered value, or FALSE if invalid
	*/
	protected function filterAttribute($attrVal, array $attrConf)
	{
		if (!empty($attrConf['filterChain']))
		{
			// Execute each filter of the chain, in order
			foreach ($attrConf['filterChain'] as $filter)
			{
				// Call the filter
				$attrVal = $this->callFilter(
					$filter,
					array(
						'attrVal'  => $attrVal,
						'attrConf' => $attrConf,
						'parser'   => $this
					),
					array(
						'attrVal'  => null,
						'attrConf' => null,
					)
				);

				// If the attribute is invalid, we break the chain and return FALSE
				if ($attrVal === false)
				{
					return false;
				}
			}
		}

		return $attrVal;
	}

	/**
	* Split parsable attributes and append them to the existing attributes
	*/
	protected function parseAttributes()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (empty($tagConfig['attributeParsers']))
		{
			return;
		}

		$attrs = array();

		foreach ($tagConfig['attributeParsers'] as $attrName => $regexps)
		{
			if (!isset($this->currentTag['attrs'][$attrName]))
			{
				continue;
			}

			foreach ($regexps as $regexp)
			{
				if (preg_match($regexp, $this->currentTag['attrs'][$attrName], $m))
				{
					foreach ($m as $k => $v)
					{
						if (!is_numeric($k))
						{
							$attrs[$k] = $v;
						}
					}

					// The attribute is removed from the current list
					unset($this->currentTag['attrs'][$attrName]);

					// We're done with this attribute
					break;
				}
			}
		}

		/**
		* Append the split attributes to the existing attributes. Values from split attributes won't
		* overwrite existing values
		*/
		$this->currentTag['attrs'] += $attrs;
	}

	//==========================================================================
	// Filters and callbacks handling
	//==========================================================================

	/**
	* Call a filter
	*
	* @param  mixed $filter     Either a string that represent a callback, or an array with at least
	*                           a "callback" key containing a valid callback
	* @params array $values     Values to be used as parameters for the callback
	* @params array $defaultSig Default signature for the callback
	* @return mixed             Callback's return value, or FALSE in case of error
	*/
	protected function callFilter($filter, array $values, array $defaultSig)
	{
		// Test whether the filter is a built-in (possible custom) filter
		if (is_string($filter) && $filter[0] === '#')
		{
			if (isset($this->filters[$filter]))
			{
				// This is a custom filter, replace the string with the definition
				$filter = $this->filters[$filter];
			}
			else
			{
				// Use the built-in filter
				$methodName = 'validate' . ucfirst(substr($filter, 1));

				if (!method_exists($this, $methodName))
				{
					$this->log('debug', array(
						'msg'    => "Unknown filter '%s'",
						'params' => array($filter)
					));

					return false;
				}

				$filter = array(
					'callback' => array($this, $methodName)
				);
			}
		}

		// Prepare the actual callback and its signature if applicable
		$callback  = $filter['callback'];
		$signature = (isset($filter['params']))
		           ? $filter['params']
		           : $defaultSig;

		// Parameters to be passed to the callback
		$params = array();

		foreach ($signature as $k => $v)
		{
			if (is_numeric($k))
			{
				$params[] = $v;
			}
			elseif (isset($values[$k]))
			{
				$params[] = $values[$k];
			}
			else
			{
				$this->log('error', array(
					'msg'    => "Unknown callback parameter '%s'",
					'params' => array($k)
				));

				return false;
			}
		}

		return call_user_func_array($callback, $params);
	}

	//==========================================================================
	// Built-in filters
	//==========================================================================

	protected function validateUrl($url)
	{
		$followedUrls = array();
		checkUrl:

		/**
		* Trim the URL to conform with HTML5
		* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
		*/
		$url = trim($url);

		/**
		* @var bool Whether to remove the scheme part of the URL
		*/
		$removeScheme = false;

		if (substr($url, 0, 2) === '//'
		 && isset($this->urlConfig['defaultScheme']))
		{
			 $url = $this->urlConfig['defaultScheme'] . ':' . $url;
			 $removeScheme = true;
		}

		/**
		* Test whether the URL contains non-ASCII characters
		*/
		if (preg_match('#[\\x80-\\xff]#', $url))
		{
			$url = self::encodeUrlToAscii($url);
		}

		$url = filter_var($url, FILTER_VALIDATE_URL);

		if (!$url)
		{
			return false;
		}

		$p = parse_url($url);

		if (!preg_match($this->urlConfig['allowedSchemes'], $p['scheme']))
		{
			$this->log('error', array(
				'msg'    => "URL scheme '%s' is not allowed",
				'params' => array($p['scheme'])
			));
			return false;
		}

		if (isset($this->urlConfig['disallowedHosts'])
		 && preg_match($this->urlConfig['disallowedHosts'], $p['host']))
		{
			$this->log('error', array(
				'msg'    => "URL host '%s' is not allowed",
				'params' => array($p['host'])
			));
			return false;
		}

		if (isset($this->urlConfig['resolveRedirectsHosts'])
		 && preg_match($this->urlConfig['resolveRedirectsHosts'], $p['host'])
		 && preg_match('#^https?#i', $p['scheme']))
		{
			if (isset($followedUrls[$url]))
			{
				$this->log('error', array(
					'msg'    => 'Infinite recursion detected while following %s',
					'params' => array($url)
				));
				return false;
			}

			$redirect = $this->getRedirectLocation($url);

			if ($redirect === false)
			{
				$this->log('error', array(
					'msg'    => 'Could not resolve %s',
					'params' => array($url)
				));
				return false;
			}

			if (isset($redirect))
			{
				$this->log('debug', array(
					'msg'    => 'Followed redirect from %1$s to %2$s',
					'params' => array($url, $redirect)
				));

				$followedUrls[$url] = 1;
				$url = $redirect;

				goto checkUrl;
			}

			$this->log('debug', array(
				'msg'    => 'No Location: received from %s',
				'params' => array($url)
			));
		}

		$pos = strpos($url, ':');

		if ($removeScheme)
		{
			$url = substr($url, $pos + 1);
		}
		else
		{
			/**
			* @link http://tools.ietf.org/html/rfc3986#section-3.1
			*
			* 'An implementation should accept uppercase letters as equivalent to lowercase in
			* scheme names (e.g., allow "HTTP" as well as "http") for the sake of robustness but
			* should only produce lowercase scheme names for consistency.'
			*/
			$url = strtolower(substr($url, 0, $pos)) . substr($url, $pos);
		}

		/**
		* We URL-encode quotes and parentheses just in case someone would want to use the URL in
		* some Javascript thingy
		*/
		$url = strtr(
			$url,
			array(
				'"' => '%22',
				"'" => '%27',
				'(' => '%28',
				')' => '%29'
			)
		);

		return $url;
	}

	/**
	* Get the "Location:" value returned by an HTTP(S) query
	*
	* @param  string $url Request URL
	* @return mixed       Location URL if applicable, FALSE in case of error, NULL if no Location
	*/
	protected function getRedirectLocation($url)
	{
		$fp = @fopen(
			$url,
			'rb',
			false,
			stream_context_create(array(
				'http' => array(
					// Bit.ly doesn't like HEAD =\
					//'method' => 'HEAD',
					'header' => "Connection: close\r\n",
					'follow_location' => false
				)
			))
		);

		if (!$fp)
		{
			return false;
		}

		$meta = stream_get_meta_data($fp);
		fclose($fp);

		foreach ($meta['wrapper_data'] as $k => $line)
		{
			if (is_numeric($k)
			 && preg_match('#^Location:(.*)#i', $line, $m))
			{
				return trim($m[1]);
			}
		}

		return null;
	}

	/**
	* Encode an UTF-8 URL to ASCII
	*
	* Requires idn_to_ascii() in order to deal with IDNs. If idn_to_ascii() is not available, the
	* host part will be URL-encoded with the rest of the URL.
	*
	* @param  string $url Original URL
	* @return Mixed       Encoded URL
	*/
	protected static function encodeUrlToAscii($url)
	{
		if (preg_match('#^(https?://(?:[^/]+@)?)([^/]+)#i', $url, $m)
		 && function_exists('idn_to_ascii'))
		{
			$url = $m[1] . idn_to_ascii($m[2]) . substr($url, strlen($m[0]));
		}

		/**
		* URL-encode non-ASCII stuff
		*/
		return preg_replace_callback(
			'#[^\\x00-\\x7f]+#u',
			function ($m)
			{
				return urlencode($m[0]);
			},
			$url
		);
	}

	protected function validateId($id)
	{
		return filter_var($id, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '#^[A-Za-z0-9\\-_]+$#D')
		));
	}

	protected function validateSimpletext($text)
	{
		return filter_var($text, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '#^[A-Za-z0-9\\-+.,_ ]+$#D')
		));
	}

	protected function validateEmail($email, array $attrConf)
	{
		$email = filter_var($email, FILTER_VALIDATE_EMAIL);

		if (!$email)
		{
			return false;
		}

		if (!empty($attrConf['forceUrlencode']))
		{
			$email = '%' . implode('%', str_split(bin2hex($email), 2));
		}

		return $email;
	}

	protected function validateInt($int)
	{
		return filter_var($int, FILTER_VALIDATE_INT);
	}

	protected function validateFloat($float)
	{
		return filter_var($float, FILTER_VALIDATE_FLOAT);
	}

	protected function validateNumber($number)
	{
		return (preg_match('#^[0-9]+$#D', $number))
			  ? $number
			  : false;
	}

	protected function validateUint($uint)
	{
		return filter_var($uint, FILTER_VALIDATE_INT, array(
			'options' => array('min_range' => 0)
		));
	}

	protected function validateRange($number, array $attrConf)
	{
		$number = filter_var($number, FILTER_VALIDATE_INT);

		if ($number === false)
		{
			return false;
		}

		if ($number < $attrConf['min'])
		{
			$this->log('warning', array(
				'msg'    => 'Value outside of range, adjusted up to %d',
				'params' => array($attrConf['min'])
			));
			return $attrConf['min'];
		}

		if ($number > $attrConf['max'])
		{
			$this->log('warning', array(
				'msg'    => 'Value outside of range, adjusted down to %d',
				'params' => array($attrConf['max'])
			));
			return $attrConf['max'];
		}

		return $number;
	}

	protected function validateColor($color)
	{
		return filter_var($color, FILTER_VALIDATE_REGEXP, array(
			'options' => array('regexp' => '/^(?:#[0-9a-f]{3,6}|[a-z]+)$/Di')
		));
	}

	protected function validateRegexp($attrVal, array $attrConf)
	{
		return (preg_match($attrConf['regexp'], $attrVal, $match))
		     ? $attrVal
		     : false;
	}
}