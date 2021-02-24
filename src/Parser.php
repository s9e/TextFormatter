<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Parser\FilterProcessing;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;

class Parser
{
	/**#@+
	* Boolean rules bitfield
	*/
	const RULE_AUTO_CLOSE        = 1 << 0;
	const RULE_AUTO_REOPEN       = 1 << 1;
	const RULE_BREAK_PARAGRAPH   = 1 << 2;
	const RULE_CREATE_PARAGRAPHS = 1 << 3;
	const RULE_DISABLE_AUTO_BR   = 1 << 4;
	const RULE_ENABLE_AUTO_BR    = 1 << 5;
	const RULE_IGNORE_TAGS       = 1 << 6;
	const RULE_IGNORE_TEXT       = 1 << 7;
	const RULE_IGNORE_WHITESPACE = 1 << 8;
	const RULE_IS_TRANSPARENT    = 1 << 9;
	const RULE_PREVENT_BR        = 1 << 10;
	const RULE_SUSPEND_AUTO_BR   = 1 << 11;
	const RULE_TRIM_FIRST_LINE   = 1 << 12;
	/**#@-*/

	/**
	* Bitwise disjunction of rules related to automatic line breaks
	*/
	const RULES_AUTO_LINEBREAKS = self::RULE_DISABLE_AUTO_BR | self::RULE_ENABLE_AUTO_BR | self::RULE_SUSPEND_AUTO_BR;

	/**
	* Bitwise disjunction of rules that are inherited by subcontexts
	*/
	const RULES_INHERITANCE = self::RULE_ENABLE_AUTO_BR;

	/**
	* All the characters that are considered whitespace
	*/
	const WHITESPACE = " \n\t";

	/**
	* @var array Number of open tags for each tag name
	*/
	protected $cntOpen;

	/**
	* @var array Number of times each tag has been used
	*/
	protected $cntTotal;

	/**
	* @var array Current context
	*/
	protected $context;

	/**
	* @var integer How hard the parser has worked on fixing bad markup so far
	*/
	protected $currentFixingCost;

	/**
	* @var Tag Current tag being processed
	*/
	protected $currentTag;

	/**
	* @var bool Whether the output contains "rich" tags, IOW any tag that is not <p> or <br/>
	*/
	protected $isRich;

	/**
	* @var Logger This parser's logger
	*/
	protected $logger;

	/**
	* @var integer How hard the parser should work on fixing bad markup
	*/
	public $maxFixingCost = 10000;

	/**
	* @var array Associative array of namespace prefixes in use in document (prefixes used as key)
	*/
	protected $namespaces;

	/**
	* @var array Stack of open tags (instances of Tag)
	*/
	protected $openTags;

	/**
	* @var string This parser's output
	*/
	protected $output;

	/**
	* @var integer Position of the cursor in the original text
	*/
	protected $pos;

	/**
	* @var array Array of callbacks, using plugin names as keys
	*/
	protected $pluginParsers = [];

	/**
	* @var array Associative array of [pluginName => pluginConfig]
	*/
	protected $pluginsConfig;

	/**
	* @var array Variables registered for use in filters
	*/
	public $registeredVars = [];

	/**
	* @var array Root context, used at the root of the document
	*/
	protected $rootContext;

	/**
	* @var array Tags' config
	*/
	protected $tagsConfig;

	/**
	* @var array Tag storage
	*/
	protected $tagStack;

	/**
	* @var bool Whether the tags in the stack are sorted
	*/
	protected $tagStackIsSorted;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* @var integer Length of the text being parsed
	*/
	protected $textLen;

	/**
	* @var integer Counter incremented everytime the parser is reset. Used to as a canary to detect
	*              whether the parser was reset during execution
	*/
	protected $uid = 0;

	/**
	* @var integer Position before which we output text verbatim, without paragraphs or linebreaks
	*/
	protected $wsPos;

	/**
	* Constructor
	*/
	public function __construct(array $config)
	{
		$this->pluginsConfig  = $config['plugins'];
		$this->registeredVars = $config['registeredVars'];
		$this->rootContext    = $config['rootContext'];
		$this->tagsConfig     = $config['tags'];

		$this->__wakeup();
	}

	/**
	* Serializer
	*
	* Returns the properties that need to persist through serialization.
	*
	* NOTE: using __sleep() is preferable to implementing Serializable because it leaves the choice
	* of the serializer to the user (e.g. igbinary)
	*
	* @return array
	*/
	public function __sleep()
	{
		return ['pluginsConfig', 'registeredVars', 'rootContext', 'tagsConfig'];
	}

	/**
	* Unserializer
	*
	* @return void
	*/
	public function __wakeup()
	{
		$this->logger = new Logger;
	}

	/**
	* Reset the parser for a new parsing
	*
	* @param  string $text Text to be parsed
	* @return void
	*/
	protected function reset($text)
	{
		// Reject invalid UTF-8
		if (!preg_match('//u', $text))
		{
			throw new InvalidArgumentException('Invalid UTF-8 input');
		}

		// Normalize CR/CRLF to LF, remove control characters that aren't allowed in XML
		$text = preg_replace('/\\r\\n?/', "\n", $text);
		$text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]+/S', '', $text);

		// Clear the logs
		$this->logger->clear();

		// Initialize the rest
		$this->cntOpen           = [];
		$this->cntTotal          = [];
		$this->currentFixingCost = 0;
		$this->currentTag        = null;
		$this->isRich            = false;
		$this->namespaces        = [];
		$this->openTags          = [];
		$this->output            = '';
		$this->pos               = 0;
		$this->tagStack          = [];
		$this->tagStackIsSorted  = false;
		$this->text              = $text;
		$this->textLen           = strlen($text);
		$this->wsPos             = 0;

		// Initialize the root context
		$this->context = $this->rootContext;
		$this->context['inParagraph'] = false;

		// Bump the UID
		++$this->uid;
	}

	/**
	* Set a tag's option
	*
	* This method ensures that the tag's config is a value and not a reference, to prevent
	* potential side-effects. References contained *inside* the tag's config are left untouched
	*
	* @param  string $tagName     Tag's name
	* @param  string $optionName  Option's name
	* @param  mixed  $optionValue Option's value
	* @return void
	*/
	protected function setTagOption($tagName, $optionName, $optionValue)
	{
		if (isset($this->tagsConfig[$tagName]))
		{
			// Copy the tag's config and remove it. That will destroy the reference
			$tagConfig = $this->tagsConfig[$tagName];
			unset($this->tagsConfig[$tagName]);

			// Set the new value and replace the tag's config
			$tagConfig[$optionName]     = $optionValue;
			$this->tagsConfig[$tagName] = $tagConfig;
		}
	}

	//==========================================================================
	// Public API
	//==========================================================================

	/**
	* Disable a tag
	*
	* @param  string $tagName Name of the tag
	* @return void
	*/
	public function disableTag($tagName)
	{
		$this->setTagOption($tagName, 'isDisabled', true);
	}

	/**
	* Enable a tag
	*
	* @param  string $tagName Name of the tag
	* @return void
	*/
	public function enableTag($tagName)
	{
		if (isset($this->tagsConfig[$tagName]))
		{
			unset($this->tagsConfig[$tagName]['isDisabled']);
		}
	}

	/**
	* Get this parser's Logger instance
	*
	* @return Logger
	*/
	public function getLogger()
	{
		return $this->logger;
	}

	/**
	* Return the last text parsed
	*
	* This method returns the normalized text, which may be slightly different from the original
	* text in that EOLs are normalized to LF and other control codes are stripped. This method is
	* meant to be used in support of processing log entries, which contain offsets based on the
	* normalized text
	*
	* @see Parser::reset()
	*
	* @return string
	*/
	public function getText()
	{
		return $this->text;
	}

	/**
	* Parse a text
	*
	* @param  string $text Text to parse
	* @return string       XML representation
	*/
	public function parse($text)
	{
		// Reset the parser and save the uid
		$this->reset($text);
		$uid = $this->uid;

		// Do the heavy lifting
		$this->executePluginParsers();
		$this->processTags();

		// Finalize the document
		$this->finalizeOutput();

		// Check the uid in case a plugin or a filter reset the parser mid-execution
		if ($this->uid !== $uid)
		{
			throw new RuntimeException('The parser has been reset during execution');
		}

		// Log a warning if the fixing cost limit was exceeded
		if ($this->currentFixingCost > $this->maxFixingCost)
		{
			$this->logger->warn('Fixing cost limit exceeded');
		}

		return $this->output;
	}

	/**
	* Change a tag's tagLimit
	*
	* NOTE: the default tagLimit should generally be set during configuration instead
	*
	* @param  string  $tagName  The tag's name, in UPPERCASE
	* @param  integer $tagLimit
	* @return void
	*/
	public function setTagLimit($tagName, $tagLimit)
	{
		$this->setTagOption($tagName, 'tagLimit', $tagLimit);
	}

	/**
	* Change a tag's nestingLimit
	*
	* NOTE: the default nestingLimit should generally be set during configuration instead
	*
	* @param  string  $tagName      The tag's name, in UPPERCASE
	* @param  integer $nestingLimit
	* @return void
	*/
	public function setNestingLimit($tagName, $nestingLimit)
	{
		$this->setTagOption($tagName, 'nestingLimit', $nestingLimit);
	}

	//==========================================================================
	// Output handling
	//==========================================================================

	/**
	* Finalize the output by appending the rest of the unprocessed text and create the root node
	*
	* @return void
	*/
	protected function finalizeOutput()
	{
		// Output the rest of the text and close the last paragraph
		$this->outputText($this->textLen, 0, true);

		// Remove empty tag pairs, e.g. <I><U></U></I> as well as empty paragraphs
		do
		{
			$this->output = preg_replace('(<([^ />]++)[^>]*></\\1>)', '', $this->output, -1, $cnt);
		}
		while ($cnt > 0);

		// Merge consecutive <i> tags
		if (strpos($this->output, '</i><i>') !== false)
		{
			$this->output = str_replace('</i><i>', '', $this->output);
		}

		// Remove control characters from the output to ensure it's valid XML
		$this->output = preg_replace('([\\x00-\\x08\\x0B-\\x1F])', '', $this->output);

		// Encode Unicode characters that are outside of the BMP
		$this->output = Utils::encodeUnicodeSupplementaryCharacters($this->output);

		// Use a <r> root if the text is rich, or <t> for plain text (including <p></p> and <br/>)
		$tagName = ($this->isRich) ? 'r' : 't';

		// Prepare the root node with all the namespace declarations
		$tmp = '<' . $tagName;
		foreach (array_keys($this->namespaces) as $prefix)
		{
			$tmp .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		$this->output = $tmp . '>' . $this->output . '</' . $tagName . '>';
	}

	/**
	* Append a tag to the output
	*
	* @param  Tag  $tag Tag to append
	* @return void
	*/
	protected function outputTag(Tag $tag)
	{
		$this->isRich = true;

		$tagName  = $tag->getName();
		$tagPos   = $tag->getPos();
		$tagLen   = $tag->getLen();
		$tagFlags = $tag->getFlags();

		if ($tagFlags & self::RULE_IGNORE_WHITESPACE)
		{
			$skipBefore = 1;
			$skipAfter  = ($tag->isEndTag()) ? 2 : 1;
		}
		else
		{
			$skipBefore = $skipAfter = 0;
		}

		// Current paragraph must end before the tag if:
		//  - the tag is a start (or self-closing) tag and it breaks paragraphs, or
		//  - the tag is an end tag (but not self-closing)
		$closeParagraph = (!$tag->isStartTag() || ($tagFlags & self::RULE_BREAK_PARAGRAPH));

		// Let the cursor catch up with this tag's position
		$this->outputText($tagPos, $skipBefore, $closeParagraph);

		// Capture the text consumed by the tag
		$tagText = ($tagLen)
		         ? htmlspecialchars(substr($this->text, $tagPos, $tagLen), ENT_NOQUOTES, 'UTF-8')
		         : '';

		// Output current tag
		if ($tag->isStartTag())
		{
			// Handle paragraphs before opening the tag
			if (!($tagFlags & self::RULE_BREAK_PARAGRAPH))
			{
				$this->outputParagraphStart($tagPos);
			}

			// Record this tag's namespace, if applicable
			$colonPos = strpos($tagName, ':');
			if ($colonPos)
			{
				$this->namespaces[substr($tagName, 0, $colonPos)] = 0;
			}

			// Open the start tag and add its attributes, but don't close the tag
			$this->output .= '<' . $tagName;

			// We output the attributes in lexical order. Helps canonicalizing the output and could
			// prove useful someday
			$attributes = $tag->getAttributes();
			ksort($attributes);

			foreach ($attributes as $attrName => $attrValue)
			{
				$this->output .= ' ' . $attrName . '="' . str_replace("\n", '&#10;', htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8')) . '"';
			}

			if ($tag->isSelfClosingTag())
			{
				if ($tagLen)
				{
					$this->output .= '>' . $tagText . '</' . $tagName . '>';
				}
				else
				{
					$this->output .= '/>';
				}
			}
			elseif ($tagLen)
			{
				$this->output .= '><s>' . $tagText . '</s>';
			}
			else
			{
				$this->output .= '>';
			}
		}
		else
		{
			if ($tagLen)
			{
				$this->output .= '<e>' . $tagText . '</e>';
			}

			$this->output .= '</' . $tagName . '>';
		}

		// Move the cursor past the tag
		$this->pos = $tagPos + $tagLen;

		// Skip newlines (no other whitespace) after this tag
		$this->wsPos = $this->pos;
		while ($skipAfter && $this->wsPos < $this->textLen && $this->text[$this->wsPos] === "\n")
		{
			// Decrement the number of lines to skip
			--$skipAfter;

			// Move the cursor past the newline
			++$this->wsPos;
		}
	}

	/**
	* Output the text between the cursor's position (included) and given position (not included)
	*
	* @param  integer $catchupPos     Position we're catching up to
	* @param  integer $maxLines       Maximum number of lines to ignore at the end of the text
	* @param  bool    $closeParagraph Whether to close the paragraph at the end, if applicable
	* @return void
	*/
	protected function outputText($catchupPos, $maxLines, $closeParagraph)
	{
		if ($closeParagraph)
		{
			if (!($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS))
			{
				$closeParagraph = false;
			}
			else
			{
				// Ignore any number of lines at the end if we're closing a paragraph
				$maxLines = -1;
			}
		}

		if ($this->pos >= $catchupPos)
		{
			// We're already there, close the paragraph if applicable and return
			if ($closeParagraph)
			{
				$this->outputParagraphEnd();
			}

			return;
		}

		// Skip over previously identified whitespace if applicable
		if ($this->wsPos > $this->pos)
		{
			$skipPos       = min($catchupPos, $this->wsPos);
			$this->output .= substr($this->text, $this->pos, $skipPos - $this->pos);
			$this->pos     = $skipPos;

			if ($this->pos >= $catchupPos)
			{
				// Skipped everything. Close the paragraph if applicable and return
				if ($closeParagraph)
				{
					$this->outputParagraphEnd();
				}

				return;
			}
		}

		// Test whether we're even supposed to output anything
		if ($this->context['flags'] & self::RULE_IGNORE_TEXT)
		{
			$catchupLen  = $catchupPos - $this->pos;
			$catchupText = substr($this->text, $this->pos, $catchupLen);

			// If the catchup text is not entirely composed of whitespace, we put it inside ignore
			// tags
			if (strspn($catchupText, " \n\t") < $catchupLen)
			{
				$catchupText = '<i>' . htmlspecialchars($catchupText, ENT_NOQUOTES, 'UTF-8') . '</i>';
			}

			$this->output .= $catchupText;
			$this->pos = $catchupPos;

			if ($closeParagraph)
			{
				$this->outputParagraphEnd();
			}

			return;
		}

		// Compute the amount of text to ignore at the end of the output
		$ignorePos = $catchupPos;
		$ignoreLen = 0;

		// Ignore as many lines (including whitespace) as specified
		while ($maxLines && --$ignorePos >= $this->pos)
		{
			$c = $this->text[$ignorePos];
			if (strpos(self::WHITESPACE, $c) === false)
			{
				break;
			}

			if ($c === "\n")
			{
				--$maxLines;
			}

			++$ignoreLen;
		}

		// Adjust $catchupPos to ignore the text at the end
		$catchupPos -= $ignoreLen;

		// Break down the text in paragraphs if applicable
		if ($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS)
		{
			if (!$this->context['inParagraph'])
			{
				$this->outputWhitespace($catchupPos);

				if ($catchupPos > $this->pos)
				{
					$this->outputParagraphStart($catchupPos);
				}
			}

			// Look for a paragraph break in this text
			$pbPos = strpos($this->text, "\n\n", $this->pos);

			while ($pbPos !== false && $pbPos < $catchupPos)
			{
				$this->outputText($pbPos, 0, true);
				$this->outputParagraphStart($catchupPos);

				$pbPos = strpos($this->text, "\n\n", $this->pos);
			}
		}

		// Capture, escape and output the text
		if ($catchupPos > $this->pos)
		{
			$catchupText = htmlspecialchars(
				substr($this->text, $this->pos, $catchupPos - $this->pos),
				ENT_NOQUOTES,
				'UTF-8'
			);

			// Format line breaks if applicable
			if (($this->context['flags'] & self::RULES_AUTO_LINEBREAKS) === self::RULE_ENABLE_AUTO_BR)
			{
				$catchupText = str_replace("\n", "<br/>\n", $catchupText);
			}

			$this->output .= $catchupText;
		}

		// Close the paragraph if applicable
		if ($closeParagraph)
		{
			$this->outputParagraphEnd();
		}

		// Add the ignored text if applicable
		if ($ignoreLen)
		{
			$this->output .= substr($this->text, $catchupPos, $ignoreLen);
		}

		// Move the cursor past the text
		$this->pos = $catchupPos + $ignoreLen;
	}

	/**
	* Output a linebreak tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputBrTag(Tag $tag)
	{
		$this->outputText($tag->getPos(), 0, false);
		$this->output .= '<br/>';
	}

	/**
	* Output an ignore tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputIgnoreTag(Tag $tag)
	{
		$tagPos = $tag->getPos();
		$tagLen = $tag->getLen();

		// Capture the text to ignore
		$ignoreText = substr($this->text, $tagPos, $tagLen);

		// Catch up with the tag's position then output the tag
		$this->outputText($tagPos, 0, false);
		$this->output .= '<i>' . htmlspecialchars($ignoreText, ENT_NOQUOTES, 'UTF-8') . '</i>';
		$this->isRich = true;

		// Move the cursor past this tag
		$this->pos = $tagPos + $tagLen;
	}

	/**
	* Start a paragraph between current position and given position, if applicable
	*
	* @param  integer $maxPos Rightmost position at which the paragraph can be opened
	* @return void
	*/
	protected function outputParagraphStart($maxPos)
	{
		// Do nothing if we're already in a paragraph, or if we don't use paragraphs
		if ($this->context['inParagraph']
		 || !($this->context['flags'] & self::RULE_CREATE_PARAGRAPHS))
		{
			return;
		}

		// Output the whitespace between $this->pos and $maxPos if applicable
		$this->outputWhitespace($maxPos);

		// Open the paragraph, but only if it's not at the very end of the text
		if ($this->pos < $this->textLen)
		{
			$this->output .= '<p>';
			$this->context['inParagraph'] = true;
		}
	}

	/**
	* Close current paragraph at current position if applicable
	*
	* @return void
	*/
	protected function outputParagraphEnd()
	{
		// Do nothing if we're not in a paragraph
		if (!$this->context['inParagraph'])
		{
			return;
		}

		$this->output .= '</p>';
		$this->context['inParagraph'] = false;
	}

	/**
	* Output the content of a verbatim tag
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function outputVerbatim(Tag $tag)
	{
		$flags = $this->context['flags'];
		$this->context['flags'] = $tag->getFlags();
		$this->outputText($this->currentTag->getPos() + $this->currentTag->getLen(), 0, false);
		$this->context['flags'] = $flags;
	}

	/**
	* Skip as much whitespace after current position as possible
	*
	* @param  integer $maxPos Rightmost character to be skipped
	* @return void
	*/
	protected function outputWhitespace($maxPos)
	{
		if ($maxPos > $this->pos)
		{
			$spn = strspn($this->text, self::WHITESPACE, $this->pos, $maxPos - $this->pos);

			if ($spn)
			{
				$this->output .= substr($this->text, $this->pos, $spn);
				$this->pos += $spn;
			}
		}
	}

	//==========================================================================
	// Plugins handling
	//==========================================================================

	/**
	* Disable a plugin
	*
	* @param  string $pluginName Name of the plugin
	* @return void
	*/
	public function disablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
		{
			// Copy the plugin's config to remove the reference
			$pluginConfig = $this->pluginsConfig[$pluginName];
			unset($this->pluginsConfig[$pluginName]);

			// Update the value and replace the plugin's config
			$pluginConfig['isDisabled'] = true;
			$this->pluginsConfig[$pluginName] = $pluginConfig;
		}
	}

	/**
	* Enable a plugin
	*
	* @param  string $pluginName Name of the plugin
	* @return void
	*/
	public function enablePlugin($pluginName)
	{
		if (isset($this->pluginsConfig[$pluginName]))
		{
			$this->pluginsConfig[$pluginName]['isDisabled'] = false;
		}
	}

	/**
	* Execute given plugin
	*
	* @param  string $pluginName Plugin's name
	* @return void
	*/
	protected function executePluginParser($pluginName)
	{
		$pluginConfig = $this->pluginsConfig[$pluginName];
		if (isset($pluginConfig['quickMatch']) && strpos($this->text, $pluginConfig['quickMatch']) === false)
		{
			return;
		}

		$matches = [];
		if (isset($pluginConfig['regexp'], $pluginConfig['regexpLimit']))
		{
			$matches = $this->getMatches($pluginConfig['regexp'], $pluginConfig['regexpLimit']);
			if (empty($matches))
			{
				return;
			}
		}

		// Execute the plugin's parser, which will add tags via $this->addStartTag() and others
		call_user_func($this->getPluginParser($pluginName), $this->text, $matches);
	}

	/**
	* Execute all the plugins
	*
	* @return void
	*/
	protected function executePluginParsers()
	{
		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
		{
			if (empty($pluginConfig['isDisabled']))
			{
				$this->executePluginParser($pluginName);
			}
		}
	}

	/**
	* Execute given regexp and returns as many matches as given limit
	*
	* @param  string  $regexp
	* @param  integer $limit
	* @return array
	*/
	protected function getMatches($regexp, $limit)
	{
		$cnt = preg_match_all($regexp, $this->text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		if ($cnt > $limit)
		{
			$matches = array_slice($matches, 0, $limit);
		}

		return $matches;
	}

	/**
	* Get the cached callback for given plugin's parser
	*
	* @param  string $pluginName Plugin's name
	* @return callable
	*/
	protected function getPluginParser($pluginName)
	{
		// Cache a new instance of this plugin's parser if there isn't one already
		if (!isset($this->pluginParsers[$pluginName]))
		{
			$pluginConfig = $this->pluginsConfig[$pluginName];
			$className = (isset($pluginConfig['className']))
			           ? $pluginConfig['className']
			           : 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';

			// Register the parser as a callback
			$this->pluginParsers[$pluginName] = [new $className($this, $pluginConfig), 'parse'];
		}

		return $this->pluginParsers[$pluginName];
	}

	/**
	* Register a parser
	*
	* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
	* existing plugin
	*
	* @param  string   $pluginName
	* @param  callback $parser
	* @param  string   $regexp
	* @param  integer  $limit
	* @return void
	*/
	public function registerParser($pluginName, $parser, $regexp = null, $limit = PHP_INT_MAX)
	{
		if (!is_callable($parser))
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');
		}
		// Create an empty config for this plugin to ensure it is executed
		if (!isset($this->pluginsConfig[$pluginName]))
		{
			$this->pluginsConfig[$pluginName] = [];
		}
		if (isset($regexp))
		{
			$this->pluginsConfig[$pluginName]['regexp']      = $regexp;
			$this->pluginsConfig[$pluginName]['regexpLimit'] = $limit;
		}
		$this->pluginParsers[$pluginName] = $parser;
	}

	//==========================================================================
	// Rules handling
	//==========================================================================

	/**
	* Apply closeAncestor rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function closeAncestor(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['closeAncestor']))
			{
				$i = count($this->openTags);

				while (--$i >= 0)
				{
					$ancestor     = $this->openTags[$i];
					$ancestorName = $ancestor->getName();

					if (isset($tagConfig['rules']['closeAncestor'][$ancestorName]))
					{
						++$this->currentFixingCost;

						// We have to close this ancestor. First we reinsert this tag...
						$this->tagStack[] = $tag;

						// ...then we add a new end tag for it with a better priority
						$this->addMagicEndTag($ancestor, $tag->getPos(), $tag->getSortPriority() - 1);

						return true;
					}
				}
			}
		}

		return false;
	}

	/**
	* Apply closeParent rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function closeParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['closeParent']))
			{
				$parent     = end($this->openTags);
				$parentName = $parent->getName();

				if (isset($tagConfig['rules']['closeParent'][$parentName]))
				{
					++$this->currentFixingCost;

					// We have to close that parent. First we reinsert the tag...
					$this->tagStack[] = $tag;

					// ...then we add a new end tag for it with a better priority
					$this->addMagicEndTag($parent, $tag->getPos(), $tag->getSortPriority() - 1);

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply the createChild rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return void
	*/
	protected function createChild(Tag $tag)
	{
		$tagConfig = $this->tagsConfig[$tag->getName()];
		if (isset($tagConfig['rules']['createChild']))
		{
			$priority = -1000;
			$tagPos   = $this->pos + strspn($this->text, " \n\r\t", $this->pos);
			foreach ($tagConfig['rules']['createChild'] as $tagName)
			{
				$this->addStartTag($tagName, $tagPos, 0, ++$priority);
			}
		}
	}

	/**
	* Apply fosterParent rules associated with given tag
	*
	* NOTE: this rule has the potential for creating an unbounded loop, either if a tag tries to
	*       foster itself or two or more tags try to foster each other in a loop. We mitigate the
	*       risk by preventing a tag from creating a child of itself (the parent still gets closed)
	*       and by checking and increasing the currentFixingCost so that a loop of multiple tags
	*       do not run indefinitely. The default tagLimit and nestingLimit also serve to prevent the
	*       loop from running indefinitely
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether a new tag has been added
	*/
	protected function fosterParent(Tag $tag)
	{
		if (!empty($this->openTags))
		{
			$tagName   = $tag->getName();
			$tagConfig = $this->tagsConfig[$tagName];

			if (!empty($tagConfig['rules']['fosterParent']))
			{
				$parent     = end($this->openTags);
				$parentName = $parent->getName();

				if (isset($tagConfig['rules']['fosterParent'][$parentName]))
				{
					if ($parentName !== $tagName && $this->currentFixingCost < $this->maxFixingCost)
					{
						$this->addFosterTag($tag, $parent);
					}

					// Reinsert current tag
					$this->tagStack[] = $tag;

					// And finally close its parent with a priority that ensures it is processed
					// before this tag
					$this->addMagicEndTag($parent, $tag->getPos(), $tag->getSortPriority() - 1);

					// Adjust the fixing cost to account for the additional tags/processing
					$this->currentFixingCost += 4;

					return true;
				}
			}
		}

		return false;
	}

	/**
	* Apply requireAncestor rules associated with given tag
	*
	* @param  Tag  $tag Tag
	* @return bool      Whether this tag has an unfulfilled requireAncestor requirement
	*/
	protected function requireAncestor(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		if (isset($tagConfig['rules']['requireAncestor']))
		{
			foreach ($tagConfig['rules']['requireAncestor'] as $ancestorName)
			{
				if (!empty($this->cntOpen[$ancestorName]))
				{
					return false;
				}
			}

			$this->logger->err('Tag requires an ancestor', [
				'requireAncestor' => implode(',', $tagConfig['rules']['requireAncestor']),
				'tag'             => $tag
			]);

			return true;
		}

		return false;
	}

	//==========================================================================
	// Tag processing
	//==========================================================================

	/**
	* Create and add a copy of a tag as a child of a given tag
	*
	* @param  Tag  $tag       Current tag
	* @param  Tag  $fosterTag Tag to foster
	* @return void
	*/
	protected function addFosterTag(Tag $tag, Tag $fosterTag)
	{
		list($childPos, $childPrio) = $this->getMagicStartCoords($tag->getPos() + $tag->getLen());

		// Add a 0-width copy of the parent tag after this tag and make it depend on this tag
		$childTag = $this->addCopyTag($fosterTag, $childPos, 0, $childPrio);
		$tag->cascadeInvalidationTo($childTag);
	}

	/**
	* Create and add an end tag for given start tag at given position
	*
	* @param  Tag     $startTag Start tag
	* @param  integer $tagPos   End tag's position (will be adjusted for whitespace if applicable)
	* @param  integer $prio     End tag's priority
	* @return Tag
	*/
	protected function addMagicEndTag(Tag $startTag, $tagPos, $prio = 0)
	{
		$tagName = $startTag->getName();

		// Adjust the end tag's position if whitespace is to be minimized
		if (($this->currentTag->getFlags() | $startTag->getFlags()) & self::RULE_IGNORE_WHITESPACE)
		{
			$tagPos = $this->getMagicEndPos($tagPos);
		}

		// Add a 0-width end tag that is paired with the given start tag
		$endTag = $this->addEndTag($tagName, $tagPos, 0, $prio);
		$endTag->pairWith($startTag);

		return $endTag;
	}

	/**
	* Compute the position of a magic end tag, adjusted for whitespace
	*
	* @param  integer $tagPos Rightmost possible position for the tag
	* @return integer
	*/
	protected function getMagicEndPos($tagPos)
	{
		// Back up from given position to the cursor's position until we find a character that
		// is not whitespace
		while ($tagPos > $this->pos && strpos(self::WHITESPACE, $this->text[$tagPos - 1]) !== false)
		{
			--$tagPos;
		}

		return $tagPos;
	}

	/**
	* Compute the position and priority of a magic start tag, adjusted for whitespace
	*
	* @param  integer   $tagPos Leftmost possible position for the tag
	* @return integer[]         [Tag pos, priority]
	*/
	protected function getMagicStartCoords($tagPos)
	{
		if (empty($this->tagStack))
		{
			// Set the next position outside the text boundaries
			$nextPos  = $this->textLen + 1;
			$nextPrio = 0;
		}
		else
		{
			$nextTag  = end($this->tagStack);
			$nextPos  = $nextTag->getPos();
			$nextPrio = $nextTag->getSortPriority();
		}

		// Find the first non-whitespace position before next tag or the end of text
		while ($tagPos < $nextPos && strpos(self::WHITESPACE, $this->text[$tagPos]) !== false)
		{
			++$tagPos;
		}

		// Set a priority that ensures this tag appears before the next tag
		$prio = ($tagPos === $nextPos) ? $nextPrio - 1 : 0;

		return [$tagPos, $prio];
	}

	/**
	* Test whether given start tag is immediately followed by a closing tag
	*
	* @param  Tag  $tag Start tag
	* @return bool
	*/
	protected function isFollowedByClosingTag(Tag $tag)
	{
		return (empty($this->tagStack)) ? false : end($this->tagStack)->canClose($tag);
	}

	/**
	* Process all tags in the stack
	*
	* @return void
	*/
	protected function processTags()
	{
		if (empty($this->tagStack))
		{
			return;
		}

		// Initialize the count tables
		foreach (array_keys($this->tagsConfig) as $tagName)
		{
			$this->cntOpen[$tagName]  = 0;
			$this->cntTotal[$tagName] = 0;
		}

		// Process the tag stack, close tags that were left open and repeat until done
		do
		{
			while (!empty($this->tagStack))
			{
				if (!$this->tagStackIsSorted)
				{
					$this->sortTags();
				}

				$this->currentTag = array_pop($this->tagStack);
				$this->processCurrentTag();
			}

			// Close tags that were left open
			foreach ($this->openTags as $startTag)
			{
				// NOTE: we add tags in hierarchical order (ancestors to descendants) but since
				//       the stack is processed in LIFO order, it means that tags get closed in
				//       the correct order, from descendants to ancestors
				$this->addMagicEndTag($startTag, $this->textLen);
			}
		}
		while (!empty($this->tagStack));
	}

	/**
	* Process current tag
	*
	* @return void
	*/
	protected function processCurrentTag()
	{
		// Invalidate current tag if tags are disabled and current tag would not close the last open
		// tag and is not a system tag
		if (($this->context['flags'] & self::RULE_IGNORE_TAGS)
		 && !$this->currentTag->canClose(end($this->openTags))
		 && !$this->currentTag->isSystemTag())
		{
			$this->currentTag->invalidate();
		}

		$tagPos = $this->currentTag->getPos();
		$tagLen = $this->currentTag->getLen();

		// Test whether the cursor passed this tag's position already
		if ($this->pos > $tagPos && !$this->currentTag->isInvalid())
		{
			// Test whether this tag is paired with a start tag and this tag is still open
			$startTag = $this->currentTag->getStartTag();

			if ($startTag && in_array($startTag, $this->openTags, true))
			{
				// Create an end tag that matches current tag's start tag, which consumes as much of
				// the same text as current tag and is paired with the same start tag
				$this->addEndTag(
					$startTag->getName(),
					$this->pos,
					max(0, $tagPos + $tagLen - $this->pos)
				)->pairWith($startTag);

				// Note that current tag is not invalidated, it's merely replaced
				return;
			}

			// If this is an ignore tag, try to ignore as much as the remaining text as possible
			if ($this->currentTag->isIgnoreTag())
			{
				$ignoreLen = $tagPos + $tagLen - $this->pos;

				if ($ignoreLen > 0)
				{
					// Create a new ignore tag and move on
					$this->addIgnoreTag($this->pos, $ignoreLen);

					return;
				}
			}

			// Skipped tags are invalidated
			$this->currentTag->invalidate();
		}

		if ($this->currentTag->isInvalid())
		{
			return;
		}

		if ($this->currentTag->isIgnoreTag())
		{
			$this->outputIgnoreTag($this->currentTag);
		}
		elseif ($this->currentTag->isBrTag())
		{
			// Output the tag if it's allowed, ignore it otherwise
			if (!($this->context['flags'] & self::RULE_PREVENT_BR))
			{
				$this->outputBrTag($this->currentTag);
			}
		}
		elseif ($this->currentTag->isParagraphBreak())
		{
			$this->outputText($this->currentTag->getPos(), 0, true);
		}
		elseif ($this->currentTag->isVerbatim())
		{
			$this->outputVerbatim($this->currentTag);
		}
		elseif ($this->currentTag->isStartTag())
		{
			$this->processStartTag($this->currentTag);
		}
		else
		{
			$this->processEndTag($this->currentTag);
		}
	}

	/**
	* Process given start tag (including self-closing tags) at current position
	*
	* @param  Tag  $tag Start tag (including self-closing)
	* @return void
	*/
	protected function processStartTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];

		// 1. Check that this tag has not reached its global limit tagLimit
		// 2. Execute this tag's filterChain, which will filter/validate its attributes
		// 3. Apply closeParent, closeAncestor and fosterParent rules
		// 4. Check for nestingLimit
		// 5. Apply requireAncestor rules
		//
		// This order ensures that the tag is valid and within the set limits before we attempt to
		// close parents or ancestors. We need to close ancestors before we can check for nesting
		// limits, whether this tag is allowed within current context (the context may change
		// as ancestors are closed) or whether the required ancestors are still there (they might
		// have been closed by a rule.)
		if ($this->cntTotal[$tagName] >= $tagConfig['tagLimit'])
		{
			$this->logger->err(
				'Tag limit exceeded',
				[
					'tag'      => $tag,
					'tagName'  => $tagName,
					'tagLimit' => $tagConfig['tagLimit']
				]
			);
			$tag->invalidate();

			return;
		}

		FilterProcessing::filterTag($tag, $this, $this->tagsConfig, $this->openTags);
		if ($tag->isInvalid())
		{
			return;
		}

		if ($this->currentFixingCost < $this->maxFixingCost)
		{
			if ($this->fosterParent($tag) || $this->closeParent($tag) || $this->closeAncestor($tag))
			{
				// This tag parent/ancestor needs to be closed, we just return (the tag is still valid)
				return;
			}
		}

		if ($this->cntOpen[$tagName] >= $tagConfig['nestingLimit'])
		{
			$this->logger->err(
				'Nesting limit exceeded',
				[
					'tag'          => $tag,
					'tagName'      => $tagName,
					'nestingLimit' => $tagConfig['nestingLimit']
				]
			);
			$tag->invalidate();

			return;
		}

		if (!$this->tagIsAllowed($tagName))
		{
			$msg     = 'Tag is not allowed in this context';
			$context = ['tag' => $tag, 'tagName' => $tagName];
			if ($tag->getLen() > 0)
			{
				$this->logger->warn($msg, $context);
			}
			else
			{
				$this->logger->debug($msg, $context);
			}
			$tag->invalidate();

			return;
		}

		if ($this->requireAncestor($tag))
		{
			$tag->invalidate();

			return;
		}

		// If this tag has an autoClose rule and it's not self-closed, paired with an end tag, or
		// immediately followed by an end tag, we replace it with a self-closing tag with the same
		// properties
		if ($tag->getFlags() & self::RULE_AUTO_CLOSE
		 && !$tag->isSelfClosingTag()
		 && !$tag->getEndTag()
		 && !$this->isFollowedByClosingTag($tag))
		{
			$newTag = new Tag(Tag::SELF_CLOSING_TAG, $tagName, $tag->getPos(), $tag->getLen());
			$newTag->setAttributes($tag->getAttributes());
			$newTag->setFlags($tag->getFlags());

			$tag = $newTag;
		}

		if ($tag->getFlags() & self::RULE_TRIM_FIRST_LINE
		 && substr($this->text, $tag->getPos() + $tag->getLen(), 1) === "\n")
		{
			$this->addIgnoreTag($tag->getPos() + $tag->getLen(), 1);
		}

		// This tag is valid, output it and update the context
		$this->outputTag($tag);
		$this->pushContext($tag);

		// Apply the createChild rules if applicable
		$this->createChild($tag);
	}

	/**
	* Process given end tag at current position
	*
	* @param  Tag  $tag end tag
	* @return void
	*/
	protected function processEndTag(Tag $tag)
	{
		$tagName = $tag->getName();

		if (empty($this->cntOpen[$tagName]))
		{
			// This is an end tag with no start tag
			return;
		}

		/**
		* @var array List of tags need to be closed before given tag
		*/
		$closeTags = [];

		// Iterate through all open tags from last to first to find a match for our tag
		$i = count($this->openTags);
		while (--$i >= 0)
		{
			$openTag = $this->openTags[$i];

			if ($tag->canClose($openTag))
			{
				break;
			}

			$closeTags[] = $openTag;
			++$this->currentFixingCost;
		}

		if ($i < 0)
		{
			// Did not find a matching tag
			$this->logger->debug('Skipping end tag with no start tag', ['tag' => $tag]);

			return;
		}

		// Accumulate flags to determine whether whitespace should be trimmed
		$flags = $tag->getFlags();
		foreach ($closeTags as $openTag)
		{
			$flags |= $openTag->getFlags();
		}
		$ignoreWhitespace = (bool) ($flags & self::RULE_IGNORE_WHITESPACE);

		// Only reopen tags if we haven't exceeded our "fixing" budget
		$keepReopening = (bool) ($this->currentFixingCost < $this->maxFixingCost);

		// Iterate over tags that are being closed, output their end tag and collect tags to be
		// reopened
		$reopenTags = [];
		foreach ($closeTags as $openTag)
		{
			$openTagName = $openTag->getName();

			// Test whether this tag should be reopened automatically
			if ($keepReopening)
			{
				if ($openTag->getFlags() & self::RULE_AUTO_REOPEN)
				{
					$reopenTags[] = $openTag;
				}
				else
				{
					$keepReopening = false;
				}
			}

			// Find the earliest position we can close this open tag
			$tagPos = $tag->getPos();
			if ($ignoreWhitespace)
			{
				$tagPos = $this->getMagicEndPos($tagPos);
			}

			// Output an end tag to close this start tag, then update the context
			$endTag = new Tag(Tag::END_TAG, $openTagName, $tagPos, 0);
			$endTag->setFlags($openTag->getFlags());
			$this->outputTag($endTag);
			$this->popContext();
		}

		// Output our tag, moving the cursor past it, then update the context
		$this->outputTag($tag);
		$this->popContext();

		// If our fixing budget allows it, peek at upcoming tags and remove end tags that would
		// close tags that are already being closed now. Also, filter our list of tags being
		// reopened by removing those that would immediately be closed
		if (!empty($closeTags) && $this->currentFixingCost < $this->maxFixingCost)
		{
			/**
			* @var integer Rightmost position of the portion of text to ignore
			*/
			$ignorePos = $this->pos;

			$i = count($this->tagStack);
			while (--$i >= 0 && ++$this->currentFixingCost < $this->maxFixingCost)
			{
				$upcomingTag = $this->tagStack[$i];

				// Test whether the upcoming tag is positioned at current "ignore" position and it's
				// strictly an end tag (not a start tag or a self-closing tag)
				if ($upcomingTag->getPos() > $ignorePos
				 || $upcomingTag->isStartTag())
				{
					break;
				}

				// Test whether this tag would close any of the tags we're about to reopen
				$j = count($closeTags);

				while (--$j >= 0 && ++$this->currentFixingCost < $this->maxFixingCost)
				{
					if ($upcomingTag->canClose($closeTags[$j]))
					{
						// Remove the tag from the lists and reset the keys
						array_splice($closeTags, $j, 1);

						if (isset($reopenTags[$j]))
						{
							array_splice($reopenTags, $j, 1);
						}

						// Extend the ignored text to cover this tag
						$ignorePos = max(
							$ignorePos,
							$upcomingTag->getPos() + $upcomingTag->getLen()
						);

						break;
					}
				}
			}

			if ($ignorePos > $this->pos)
			{
				/**
				* @todo have a method that takes (pos,len) rather than a Tag
				*/
				$this->outputIgnoreTag(new Tag(Tag::SELF_CLOSING_TAG, 'i', $this->pos, $ignorePos - $this->pos));
			}
		}

		// Re-add tags that need to be reopened, at current cursor position
		foreach ($reopenTags as $startTag)
		{
			$newTag = $this->addCopyTag($startTag, $this->pos, 0);

			// Re-pair the new tag
			$endTag = $startTag->getEndTag();
			if ($endTag)
			{
				$newTag->pairWith($endTag);
			}
		}
	}

	/**
	* Update counters and replace current context with its parent context
	*
	* @return void
	*/
	protected function popContext()
	{
		$tag = array_pop($this->openTags);
		--$this->cntOpen[$tag->getName()];
		$this->context = $this->context['parentContext'];
	}

	/**
	* Update counters and replace current context with a new context based on given tag
	*
	* If given tag is a self-closing tag, the context won't change
	*
	* @param  Tag  $tag Start tag (including self-closing)
	* @return void
	*/
	protected function pushContext(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagFlags  = $tag->getFlags();
		$tagConfig = $this->tagsConfig[$tagName];

		++$this->cntTotal[$tagName];

		// If this is a self-closing tag, the context remains the same
		if ($tag->isSelfClosingTag())
		{
			return;
		}

		// Recompute the allowed tags
		$allowed = [];
		foreach ($this->context['allowed'] as $k => $v)
		{
			// If the current tag is not transparent, override the low bits (allowed children) of
			// current context with its high bits (allowed descendants)
			if (!($tagFlags & self::RULE_IS_TRANSPARENT))
			{
				$v = ($v & 0xFF00) | ($v >> 8);
			}
			$allowed[] = $tagConfig['allowed'][$k] & $v;
		}

		// Use this tag's flags as a base for this context and add inherited rules
		$flags = $tagFlags | ($this->context['flags'] & self::RULES_INHERITANCE);

		// RULE_DISABLE_AUTO_BR turns off RULE_ENABLE_AUTO_BR
		if ($flags & self::RULE_DISABLE_AUTO_BR)
		{
			$flags &= ~self::RULE_ENABLE_AUTO_BR;
		}

		++$this->cntOpen[$tagName];
		$this->openTags[] = $tag;
		$this->context = [
			'allowed'       => $allowed,
			'flags'         => $flags,
			'inParagraph'   => false,
			'parentContext' => $this->context
		];
	}

	/**
	* Return whether given tag is allowed in current context
	*
	* @param  string $tagName
	* @return bool
	*/
	protected function tagIsAllowed($tagName)
	{
		$n = $this->tagsConfig[$tagName]['bitNumber'];

		return (bool) ($this->context['allowed'][$n >> 3] & (1 << ($n & 7)));
	}

	//==========================================================================
	// Tag stack
	//==========================================================================

	/**
	* Add a start tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	public function addStartTag($name, $pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::START_TAG, $name, $pos, $len, $prio);
	}

	/**
	* Add an end tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	public function addEndTag($name, $pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::END_TAG, $name, $pos, $len, $prio);
	}

	/**
	* Add a self-closing tag
	*
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	public function addSelfClosingTag($name, $pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, $name, $pos, $len, $prio);
	}

	/**
	* Add a 0-width "br" tag to force a line break at given position
	*
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	public function addBrTag($pos, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'br', $pos, 0, $prio);
	}

	/**
	* Add an "ignore" tag
	*
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	public function addIgnoreTag($pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'i', $pos, min($len, $this->textLen - $pos), $prio);
	}

	/**
	* Add a paragraph break at given position
	*
	* Uses a zero-width tag that is actually never output in the result
	*
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	public function addParagraphBreak($pos, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'pb', $pos, 0, $prio);
	}

	/**
	* Add a copy of given tag at given position and length
	*
	* @param  Tag     $tag  Original tag
	* @param  integer $pos  Copy's position
	* @param  integer $len  Copy's length
	* @param  integer $prio Copy's priority (same as original by default)
	* @return Tag           Copy tag
	*/
	public function addCopyTag(Tag $tag, $pos, $len, $prio = null)
	{
		if (!isset($prio))
		{
			$prio = $tag->getSortPriority();
		}
		$copy = $this->addTag($tag->getType(), $tag->getName(), $pos, $len, $prio);
		$copy->setAttributes($tag->getAttributes());

		return $copy;
	}

	/**
	* Add a tag
	*
	* @param  integer $type Tag's type
	* @param  string  $name Name of the tag
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	protected function addTag($type, $name, $pos, $len, $prio)
	{
		// Create the tag
		$tag = new Tag($type, $name, $pos, $len, $prio);

		// Set this tag's rules bitfield
		if (isset($this->tagsConfig[$name]))
		{
			$tag->setFlags($this->tagsConfig[$name]['rules']['flags']);
		}

		// Invalidate this tag if it's an unknown tag, a disabled tag, if either of its length or
		// position is negative or if it's out of bounds
		if ((!isset($this->tagsConfig[$name]) && !$tag->isSystemTag())
		 || $this->isInvalidTextSpan($pos, $len))
		{
			$tag->invalidate();
		}
		elseif (!empty($this->tagsConfig[$name]['isDisabled']))
		{
			$this->logger->warn(
				'Tag is disabled',
				[
					'tag'     => $tag,
					'tagName' => $name
				]
			);
			$tag->invalidate();
		}
		else
		{
			$this->insertTag($tag);
		}

		return $tag;
	}

	/**
	* Test whether given text span is outside text boundaries or an invalid UTF sequence
	*
	* @param  integer $pos Start of text
	* @param  integer $len Length of text
	* @return bool
	*/
	protected function isInvalidTextSpan($pos, $len)
	{
		return ($len < 0 || $pos < 0 || $pos + $len > $this->textLen || preg_match('([\\x80-\\xBF])', substr($this->text, $pos, 1) . substr($this->text, $pos + $len, 1)));
	}

	/**
	* Insert given tag in the tag stack
	*
	* @param  Tag  $tag
	* @return void
	*/
	protected function insertTag(Tag $tag)
	{
		if (!$this->tagStackIsSorted)
		{
			$this->tagStack[] = $tag;
		}
		else
		{
			// Scan the stack and copy every tag to the next slot until we find the correct index
			$i   = count($this->tagStack);
			$key = $this->getSortKey($tag);
			while ($i > 0 && $key > $this->getSortKey($this->tagStack[$i - 1]))
			{
				$this->tagStack[$i] = $this->tagStack[$i - 1];
				--$i;
			}
			$this->tagStack[$i] = $tag;
		}
	}

	/**
	* Add a pair of tags
	*
	* @param  string  $name     Name of the tags
	* @param  integer $startPos Position of the start tag
	* @param  integer $startLen Length of the start tag
	* @param  integer $endPos   Position of the start tag
	* @param  integer $endLen   Length of the start tag
	* @param  integer $prio     Start tag's priority (the end tag will be set to minus that value)
	* @return Tag               Start tag
	*/
	public function addTagPair($name, $startPos, $startLen, $endPos, $endLen, $prio = 0)
	{
		// NOTE: the end tag is added first to try to keep the stack in the correct order
		$endTag   = $this->addEndTag($name, $endPos, $endLen, -$prio);
		$startTag = $this->addStartTag($name, $startPos, $startLen, $prio);
		$startTag->pairWith($endTag);

		return $startTag;
	}

	/**
	* Add a tag that represents a verbatim copy of the original text
	*
	* @param  integer $pos  Position of the tag in the text
	* @param  integer $len  Length of text consumed by the tag
	* @param  integer $prio Tag's priority
	* @return Tag
	*/
	public function addVerbatim($pos, $len, $prio = 0)
	{
		return $this->addTag(Tag::SELF_CLOSING_TAG, 'v', $pos, $len, $prio);
	}

	/**
	* Sort tags by position and precedence
	*
	* @return void
	*/
	protected function sortTags()
	{
		$arr = [];
		foreach ($this->tagStack as $i => $tag)
		{
			$key       = $this->getSortKey($tag, $i);
			$arr[$key] = $tag;
		}
		krsort($arr);

		$this->tagStack         = array_values($arr);
		$this->tagStackIsSorted = true;
	}

	/**
	* Generate a key for given tag that can be used to compare its position using lexical comparisons
	*
	* Tags are sorted by position first, then by priority, then by whether they consume any text,
	* then by length, and finally in order of their creation.
	*
	* The stack's array is in reverse order. Therefore, tags that appear at the start of the text
	* are at the end of the array.
	*
	* @param  Tag     $tag
	* @param  integer $tagIndex
	* @return string
	*/
	protected function getSortKey(Tag $tag, int $tagIndex = 0): string
	{
		// Ensure that negative values are sorted correctly by flagging them and making them positive
		$prioFlag = ($tag->getSortPriority() >= 0);
		$prio     = $tag->getSortPriority();
		if (!$prioFlag)
		{
			$prio += (1 << 30);
		}

		// Sort 0-width tags separately from the rest
		$lenFlag = ($tag->getLen() > 0);
		if ($lenFlag)
		{
			// Inverse their length so that longest matches are processed first
			$lenOrder = $this->textLen - $tag->getLen();
		}
		else
		{
			// Sort self-closing tags in-between start tags and end tags to keep them outside of tag
			// pairs
			$order = [
				Tag::END_TAG          => 0,
				Tag::SELF_CLOSING_TAG => 1,
				Tag::START_TAG        => 2
			];
			$lenOrder = $order[$tag->getType()];
		}

		return sprintf('%8x%d%8x%d%8x%8x', $tag->getPos(), $prioFlag, $prio, $lenFlag, $lenOrder, $tagIndex);
	}
}