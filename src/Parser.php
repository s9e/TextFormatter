<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2011 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use RuntimeException,
    XMLWriter;

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
	* @var array Logged messages, reinitialized whenever a text is parsed
	*/
	protected $log = array();

	/**
	* @var array Tags config
	*/
	protected $tagsConfig;

	/**
	* @var array Plugins config
	*/
	protected $pluginsConfig;

	/**
	* @var array Filters config
	*/
	protected $filtersConfig;

	/**
	* @var array Registered namespaces: [prefix => uri]
	*/
	protected $registeredNamespaces = array();

	/**
	* @var array Array of PluginParser instances
	*/
	protected $pluginParsers = array();

	//==============================================================================================
	// Per-formatting vars
	//==============================================================================================

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
		$this->filtersConfig = $config['filters'];
		$this->pluginsConfig = $config['plugins'];
		$this->tagsConfig    = $config['tags'];

		if (isset($config['namespaces']))
		{
			$this->registeredNamespaces = $config['namespaces'];
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
	* Filter a var according to the configuration's filters
	*
	* Used internally but made public so that developers can test in advance whether a var would be
	* invalid. It mostly relies on PHP's own ext/filter extension but individual filters can be
	* overwritten by the config
	*
	* @param  mixed  $attrVal    Attribute value to be filtered/sanitized
	* @param  array  $attrConf   Attribute configuration
	* @param  array  $filterConf Filter configuration
	* @param  Parser $parser     The Parser instance that has called this filter
	* @return mixed              The sanitized value of this attribute, or false if it was invalid
	*/
	static public function filter($attrVal, array $attrConf, array $filterConf, Parser $parser)
	{
		switch ($attrConf['type'])
		{
			case 'url':
				$followedUrls = array();
				checkUrl:

				/**
				* Trim the URL to conform with HTML5
				* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
				*/
				$attrVal = trim($attrVal);

				/**
				* @var bool Whether to remove the scheme part of the URL
				*/
				$removeScheme = false;

				if (substr($attrVal, 0, 2) === '//'
				 && isset($filterConf['defaultScheme']))
				{
					 $attrVal = $filterConf['defaultScheme'] . ':' . $attrVal;
					 $removeScheme = true;
				}

				/**
				* Test whether the URL contains non-ASCII characters
				*/
				if (preg_match('#[\\x80-\\xff]#', $attrVal))
				{
					$attrVal = static::encodeUrlToAscii($attrVal);
				}

				$attrVal = filter_var($attrVal, \FILTER_VALIDATE_URL);

				if (!$attrVal)
				{
					return false;
				}

				$p = parse_url($attrVal);

				if (!preg_match($filterConf['allowedSchemes'], $p['scheme']))
				{
					$parser->log('error', array(
						'msg'    => "URL scheme '%s' is not allowed",
						'params' => array($p['scheme'])
					));
					return false;
				}

				if (isset($filterConf['disallowedHosts'])
				 && preg_match($filterConf['disallowedHosts'], $p['host']))
				{
					$parser->log('error', array(
						'msg'    => "URL host '%s' is not allowed",
						'params' => array($p['host'])
					));
					return false;
				}

				if (isset($filterConf['resolveRedirectsHosts'])
				 && preg_match($filterConf['resolveRedirectsHosts'], $p['host'])
				 && preg_match('#^https?#i', $p['scheme']))
				{
					if (isset($followedUrls[$attrVal]))
					{
						$parser->log('error', array(
							'msg'    => 'Infinite recursion detected while following %s',
							'params' => array($attrVal)
						));
						return false;
					}

					$url = self::getRedirectLocation($attrVal);

					if ($url === false)
					{
						$parser->log('error', array(
							'msg'    => 'Could not resolve %s',
							'params' => array($attrVal)
						));
						return false;
					}

					if (isset($url))
					{
						$parser->log('debug', array(
							'msg'    => 'Followed redirect from %1$s to %2$s',
							'params' => array($attrVal, $url)
						));

						$followedUrls[$attrVal] = 1;
						$attrVal = $url;

						goto checkUrl;
					}

					$parser->log('debug', array(
						'msg'    => 'No Location: received from %s',
						'params' => array($attrVal)
					));
				}

				if ($removeScheme)
				{
					$attrVal = substr($attrVal, 1 + strpos($attrVal, ':'));
				}

				/**
				* We URL-encode quotes just in case someone would want to use the URL in some
				* Javascript thingy
				*/
				return str_replace(array("'", '"'), array('%27', '%22'), $attrVal);

			case 'identifier':
			case 'id':
				return filter_var($attrVal, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '#^[A-Za-z0-9\\-_]+$#D')
				));

			case 'simpletext':
				return filter_var($attrVal, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '#^[A-Za-z0-9\\-+.,_ ]+$#D')
				));

			case 'text':
				return (string) $attrVal;

			case 'email':
				$attrVal = filter_var($attrVal, \FILTER_VALIDATE_EMAIL);

				if (!$attrVal)
				{
					return false;
				}

				if (!empty($attrConf['forceUrlencode']))
				{
					$attrVal = '%' . implode('%', str_split(bin2hex($attrVal), 2));
				}

				return $attrVal;

			case 'int':
			case 'integer':
				return filter_var($attrVal, \FILTER_VALIDATE_INT);

			case 'float':
				return filter_var($attrVal, \FILTER_VALIDATE_FLOAT);

			case 'number':
				return (preg_match('#^[0-9]+$#D', $attrVal))
				      ? $attrVal
				      : false;

			case 'uint':
				return filter_var($attrVal, \FILTER_VALIDATE_INT, array(
					'options' => array('min_range' => 0)
				));

			case 'range':
				$attrVal = filter_var($attrVal, \FILTER_VALIDATE_INT);

				if ($attrVal === false)
				{
					return false;
				}

				if ($attrVal < $attrConf['min'])
				{
					$parser->log('warning', array(
						'msg'    => 'Value outside of range, adjusted up to %d',
						'params' => array($attrConf['min'])
					));
					return $attrConf['min'];
				}

				if ($attrVal > $attrConf['max'])
				{
					$parser->log('warning', array(
						'msg'    => 'Value outside of range, adjusted down to %d',
						'params' => array($attrConf['max'])
					));
					return $attrConf['max'];
				}

				return $attrVal;

			case 'color':
				return filter_var($attrVal, \FILTER_VALIDATE_REGEXP, array(
					'options' => array('regexp' => '/^(?:#[0-9a-f]{3,6}|[a-z]+)$/Di')
				));

			case 'regexp':
				if (!preg_match($attrConf['regexp'], $attrVal, $match))
				{
					return false;
				}

				if (isset($attrConf['replaceWith']))
				{
					/**
					* Two consecutive backslashes[1] are replaced with a single backslash.
					* A dollar sign preceded by a backslash[2] and followed an optional curly
					* bracket followed by digits is preserved.
					* Otherwise, the corresponding match[3] is used.
					*/
					return preg_replace_callback(
						'#(\\\\\\\\)|(\\\\)?\\$([0-9]+|\\{[0-9]+\\})#',
						function ($m) use ($match)
						{
							if (isset($m[3]))
							{
								$m[3] = trim($m[3], '{}');
							}

							return ($m[1]) ? '\\' : (($m[2]) ? '$' . $m[3] : $match[$m[3]]);
						},
						$attrConf['replaceWith']
					);
				}

				return $attrVal;
		}

		$parser->log('debug', array(
			'msg'    => "Unknown filter '%s'",
			'params' => array($attrConf['type'])
		));
		return false;
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
				\PREG_SET_ORDER | \PREG_OFFSET_CAPTURE
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

		if (!$isArray)
		{
			$matches = $matches[0];
		}

		return $matches;
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
			if (strpos($tag['name'], ':') !== false)
			{
				$this->hasNamespacedTags = true;
			}
			else
			{
				$tag['name'] = strtoupper($tag['name']);
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

		$this->context = array(
			'allowedChildren'    => str_repeat("\xff", ceil(count($this->tagsConfig) / 8)),
			'allowedDescendants' => str_repeat("\xff", ceil(count($this->tagsConfig) / 8))
		);

		foreach ($this->tagsConfig as $tagConfig)
		{
			if (!empty($tagConfig['disallowAsRoot']))
			{
				$n = $tagConfig['n'];

				$this->context['allowedChildren'][$n >> 3]
					= $this->context['allowedChildren'][$n >> 3] ^ chr(1 << ($n & 7));
			}
		}

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
	* Process currentTag
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
				'msg' => 'Tag skipped due to dependence'
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
		//==============================================================
		// Apply closeParent and closeAncestor rules
		//==============================================================

		if ($this->closeParent()
		 || $this->closeAncestor())
		{
			return;
		}

		$tagName   = $this->currentTag['name'];
		$tagConfig = $this->tagsConfig[$tagName];

		if ($this->cntOpen[$tagName]  >= $tagConfig['nestingLimit']
		 || $this->cntTotal[$tagName] >= $tagConfig['tagLimit'])
		{
			return;
		}

		//==============================================================
		// Check that this tag is allowed here
		//==============================================================

		if (!$this->tagIsAllowed($tagName))
		{
			$this->log('debug', array(
				'msg'    => 'Tag %s is not allowed in this context',
				'params' => array($tagName)
			));
			return;
		}

		if ($this->requireParent()
		 || $this->requireAncestor()
		 || $this->processCurrentTagAttributes())
		{
			return;
		}

		//==============================================================
		// We have a valid tag, append it to the list of processed tags
		//==============================================================

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
			'tagMate'    => $tag['tagMate'],
			'pluginName' => $tag['pluginName']
		);

		$this->addTrimmingInfoToTag($newTag);

		return $newTag;
	}

	/**
	* Apply closeParent rules from current tag
	*
	* @return boolean Whether a new tag has been added
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
	* @return boolean Whether a new tag has been added
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
	* @return boolean Whether current tag is invalid
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
	* @return boolean Whether current tag is invalid
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
	* @return boolean TRUE if the tag's requirements were NOT fulfilled, FALSE otherwise
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
	static protected function compareTags(array $a, array $b)
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
			return (!$a['len']) ? 1 : -1;
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
	* Process attributes from current tag
	*
	* Will add default values, execute phase callbacks and remove undefined attributes.
	*
	* @return boolean Whether the set of attributes is invalid
	*/
	protected function processCurrentTagAttributes()
	{
		if (empty($this->tagsConfig[$this->currentTag['name']]['attrs']))
		{
			/**
			* Remove all attributes if none are defined for this tag
			*/
			$this->currentTag['attrs'] = array();
		}
		else
		{
			/**
			* Handle compound attributes
			*/
			$this->splitCompoundAttributes();

			/**
			* Filter attributes
			*/
			$this->filterAttributes();

			/**
			* Add default values
			*/
			$this->addDefaultAttributeValuesToCurrentTag();

			/**
			* Check for missing required attributes
			*/
			if ($this->currentTagRequiresMissingAttribute())
			{
				return true;
			}

			/**
			* Sort attributes alphabetically. Can be useful if someone wants to process the
			* output using regexps
			*/
			ksort($this->currentTag['attrs']);
		}

		return false;
	}

	/**
	* Test whether current tag is missing any required attributes
	*
	* @return boolean
	*/
	protected function currentTagRequiresMissingAttribute()
	{
		$missingAttrs = array_diff_key(
			$this->tagsConfig[$this->currentTag['name']]['attrs'],
			$this->currentTag['attrs']
		);

		foreach ($missingAttrs as $attrName => $attrConf)
		{
			if (empty($attrConf['isRequired']))
			{
				continue;
			}

			$this->log('error', array(
				'msg'    => "Missing attribute '%s'",
				'params' => array($attrName)
			));

			return true;
		}

		return false;
	}

	/**
	* Add default values to current tag's attributes
	*/
	protected function addDefaultAttributeValuesToCurrentTag()
	{
		$missingAttrs = array_diff_key(
			$this->tagsConfig[$this->currentTag['name']]['attrs'],
			$this->currentTag['attrs']
		);

		foreach ($missingAttrs as $attrName => $attrConf)
		{
			if (isset($attrConf['defaultValue']))
			{
				$this->currentTag['attrs'][$attrName] = $attrConf['defaultValue'];
			}
		}
	}

	/**
	* Filter attributes from current tag
	*/
	protected function filterAttributes()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		/**
		* Tag-level preFilter callbacks
		*/
		$this->applyTagPreFilterCallbacks();

		/**
		* Remove undefined attributes
		*/
		$this->removeUndefinedAttributesFromCurrentTag();

		/**
		* Filter each attribute
		*/
		foreach ($this->currentTag['attrs'] as $attrName => $originalVal)
		{
			$this->currentAttribute = $attrName;

			// execute preFilter callbacks
			$this->applyAttributePreFilterCallbacks();

			// do filter/validate current attribute
			$this->filterCurrentAttribute();

			// if the value is invalid, log the occurence, remove the attribute then skip to the
			// next attribute
			if ($this->currentTag['attrs'][$attrName] === false)
			{
				$this->log('error', array(
					'msg'    => "Invalid attribute '%s'",
					'params' => array($attrName)
				));

				unset($this->currentTag['attrs'][$attrName]);

				continue;
			}

			// execute postFilter callbacks
			$this->applyAttributePostFilterCallbacks();

			if ($originalVal !== $this->currentTag['attrs'][$attrName])
			{
				$this->log('debug', array(
					'msg'    => 'Attribute value was altered by the filter '
					          . '(attrName: %1$s, originalVal: %2$s, attrVal: %3$s)',
					'params' => array(
						$attrName,
						var_export($originalVal, true),
						var_export($this->currentTag['attrs'][$attrName], true)
					)
				));
			}
		}
		unset($this->currentAttribute);

		/**
		* Tag-level postFilter callbacks
		*/
		$this->applyTagPostFilterCallbacks();
	}

	/**
	* Removed undefined attributes from current tag
	*/
	protected function removeUndefinedAttributesFromCurrentTag()
	{
		$this->currentTag['attrs'] = array_intersect_key(
			$this->currentTag['attrs'],
			$this->tagsConfig[$this->currentTag['name']]['attrs']
		);
	}

	/**
	* Filter current attribute
	*/
	protected function filterCurrentAttribute()
	{
		$tagConfig   = $this->tagsConfig[$this->currentTag['name']];
		$attrConf    = $tagConfig['attrs'][$this->currentAttribute];
		$filterConf  = (isset($this->filtersConfig[$attrConf['type']]))
		             ? $this->filtersConfig[$attrConf['type']]
		             : array();

		// if a filter isn't set for that type, use the built-in method
		if (!isset($filterConf['callback']))
		{
			$filterConf['callback'] = array(__CLASS__, 'filter');
			$filterConf['params']   = array(
				'attrVal'    => null,
				'attrConf'   => null,
				'filterConf' => $filterConf,
				'parser'     => null
			);
		}

		// filter the value
		$this->currentTag['attrs'][$this->currentAttribute] = $this->applyCallback(
			$filterConf,
			array(
				'attrVal'  => $this->currentTag['attrs'][$this->currentAttribute],
				'attrConf' => $attrConf
			)
		);
	}

	/**
	* Execute/apply the preFilter callbacks from current tag
	*/
	protected function applyTagPreFilterCallbacks()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (isset($tagConfig['preFilter']))
		{
			foreach ($tagConfig['preFilter'] as $callbackConf)
			{
				$this->currentTag['attrs'] = $this->applyCallback(
					$callbackConf,
					array('attrs' => $this->currentTag['attrs'])
				);
			}
		}
	}

	/**
	* Execute/apply the postFilter callbacks from current tag
	*/
	protected function applyTagPostFilterCallbacks()
	{
		$tagConfig = $this->tagsConfig[$this->currentTag['name']];

		if (isset($tagConfig['postFilter']))
		{
			foreach ($tagConfig['postFilter'] as $callbackConf)
			{
				$this->currentTag['attrs'] = $this->applyCallback(
					$callbackConf,
					array('attrs' => $this->currentTag['attrs'])
				);
			}
		}
	}

	/**
	* Execute/apply preFilter callbacks to current attribute
	*/
	protected function applyAttributePreFilterCallbacks()
	{
		$attrConf = $this->tagsConfig[$this->currentTag['name']]['attrs'][$this->currentAttribute];

		if (!empty($attrConf['preFilter']))
		{
			foreach ($attrConf['preFilter'] as $callbackConf)
			{
				$this->currentTag['attrs'][$this->currentAttribute] = $this->applyCallback(
					$callbackConf,
					array('attrVal' => $this->currentTag['attrs'][$this->currentAttribute])
				);
			}
		}
	}

	/**
	* Execute/apply postFilter callbacks to current attribute
	*/
	protected function applyAttributePostFilterCallbacks()
	{
		$attrConf = $this->tagsConfig[$this->currentTag['name']]['attrs'][$this->currentAttribute];

		if (!empty($attrConf['postFilter']))
		{
			foreach ($attrConf['postFilter'] as $callbackConf)
			{
				$this->currentTag['attrs'][$this->currentAttribute] = $this->applyCallback(
					$callbackConf,
					array('attrVal' => $this->currentTag['attrs'][$this->currentAttribute])
				);
			}
		}
	}

	/**
	* Apply a callback and return the result
	*
	* @param  array $conf   Callback configuration. Must have a "callback" element and can have an
	*                       optional "params" element. If there's no "params" element, $value is
	*                       passed as the only argument to the callback
	* @param  array $values Values used to replace values found in the "params" element
	* @return mixed
	*/
	protected function applyCallback(array $conf, array $values = array())
	{
		$params = array();

		if (isset($conf['params']))
		{
			/**
			* Replace the dynamic parameters with their current value
			*/
			$values += array(
				'parser'        => $this,
				'tagsConfig'    => $this->tagsConfig,
				'filtersConfig' => $this->filtersConfig
			);

			foreach (array('currentTag', 'currentAttribute') as $k)
			{
				if (isset($this->$k) && !isset($values[$k]))
				{
					$values[$k] = $this->$k;
				}
			}

			$params = array_replace(
				$conf['params'],
				array_intersect_key($values, $conf['params'])
			);
		}

		return call_user_func_array($conf['callback'], $params);
	}

	/**
	* Split compound attributes and append them to the existing attributes
	*/
	protected function splitCompoundAttributes()
	{
		$tagConfig   = $this->tagsConfig[$this->currentTag['name']];
		$attrsConfig = array_intersect_key($tagConfig['attrs'], $this->currentTag['attrs']);

		$attrs = array();

		foreach ($attrsConfig as $attrName => $attrConfig)
		{
			if ($attrConfig['type'] !== 'compound')
			{
				continue;
			}

			if (preg_match($attrConfig['regexp'], $this->currentTag['attrs'][$attrName], $m))
			{
				foreach ($m as $k => $v)
				{
					if (!is_numeric($k))
					{
						$attrs[$k] = $v;
					}
				}
			}

			/**
			* Compound attributes are removed from the aray
			*/
			unset($this->currentTag['attrs'][$attrName]);
		}

		/**
		* Append the split attributes to the existing attributes. Values from split attributes won't
		* overwrite existing values
		*/
		$this->currentTag['attrs'] += $attrs;
	}

	/**
	* Get the Location: value return by a HTTP(S) query
	*
	* @param  string $url Request URL
	* @return mixed       Location URL if applicable, FALSE in case of error, NULL if no Location
	*/
	static protected function getRedirectLocation($url)
	{
		$fp = @fopen(
			$url,
			'rb',
			false,
			stream_context_create(array(
				'http' => array(
					// Bit.ly doesn't like HEAD =\
//					'method' => 'HEAD',
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
	static protected function encodeUrlToAscii($url)
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
}