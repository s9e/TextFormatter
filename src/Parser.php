<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Parser\Logger;
use s9e\TextFormatter\Parser\Tag;

class Parser
{
	use Parser\RulesHandling;
	use Parser\TagProcessing;
	use Parser\TagStack;

	/**#@+
	* Boolean rules bitfield
	*/
	const RULE_AUTO_CLOSE        =   1;
	const RULE_AUTO_REOPEN       =   2;
	const RULE_BREAK_PARAGRAPH   =   4;
	const RULE_CREATE_PARAGRAPHS =   8;
	const RULE_IGNORE_TAGS       =  16;
	const RULE_IGNORE_TEXT       =  32;
	const RULE_IS_TRANSPARENT    =  64;
	const RULE_NO_BR_CHILD       = 128;
	const RULE_NO_BR_DESCENDANT  = 256;
	const RULE_TRIM_WHITESPACE   = 512;
	/**#@-*/

	/**
	* All the characters that are considered whitespace
	*/
	const WHITESPACE = " \n\t";

	/**
	* @var bool Whether the output contains "rich" tags, IOW any tag that is not <p> or <br/>
	*/
	protected $isRich;

	/**
	* @var Logger This parser's logger
	*/
	protected $logger;

	/**
	* @var array Associative array of namespace prefixes in use in document (prefixes used as key)
	*/
	protected $namespaces;

	/**
	* @var string This parser's output
	*/
	protected $output;

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
	* @var array Tags' config
	*/
	protected $tagsConfig;

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
		// Normalize CR/CRLF to LF, remove control characters that aren't allowed in XML
		$text = preg_replace('/\\r\\n?/', "\n", $text);
		$text = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]+/S', '', $text);

		// Clear the logs
		$this->logger->clear();

		// Initialize the rest
		$this->currentFixingCost = 0;
		$this->isRich     = false;
		$this->namespaces = [];
		$this->output     = '';
		$this->text       = $text;
		$this->textLen    = strlen($text);
		$this->tagStack   = [];
		$this->tagStackIsSorted = true;
		$this->wsPos      = 0;

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

		// Check the uid in case a plugin or a filter reset the parser mid-execution
		if ($this->uid !== $uid)
		{
			throw new RuntimeException('The parser has been reset during execution');
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
	// Filter processing
	//==========================================================================

	/**
	* Execute all the attribute preprocessors of given tag
	*
	* @private
	*
	* @param  Tag   $tag       Source tag
	* @param  array $tagConfig Tag's config
	* @return bool             Unconditionally TRUE
	*/
	public static function executeAttributePreprocessors(Tag $tag, array $tagConfig)
	{
		if (!empty($tagConfig['attributePreprocessors']))
		{
			foreach ($tagConfig['attributePreprocessors'] as list($attrName, $regexp))
			{
				if (!$tag->hasAttribute($attrName))
				{
					continue;
				}

				$attrValue = $tag->getAttribute($attrName);

				// If the regexp matches, we add the captured attributes
				if (preg_match($regexp, $attrValue, $m))
				{
					// Set the target attributes
					foreach ($m as $targetName => $targetValue)
					{
						// Skip numeric captures and empty captures
						if (is_numeric($targetName) || $targetValue === '')
						{
							continue;
						}

						// Attribute preprocessors cannot overwrite other attributes but they can
						// overwrite themselves
						if ($targetName === $attrName || !$tag->hasAttribute($targetName))
						{
							$tag->setAttribute($targetName, $targetValue);
						}
					}
				}
			}
		}

		return true;
	}

	/**
	* Execute a filter
	*
	* @see s9e\TextFormatter\Configurator\Items\ProgrammableCallback
	*
	* @param  array $filter Programmed callback
	* @param  array $vars   Variables to be used when executing the callback
	* @return mixed         Whatever the callback returns
	*/
	protected static function executeFilter(array $filter, array $vars)
	{
		$callback = $filter['callback'];
		$params   = (isset($filter['params'])) ? $filter['params'] : [];

		$args = [];
		foreach ($params as $k => $v)
		{
			if (is_numeric($k))
			{
				// By-value param
				$args[] = $v;
			}
			elseif (isset($vars[$k]))
			{
				// By-name param using a supplied var
				$args[] = $vars[$k];
			}
			elseif (isset($vars['registeredVars'][$k]))
			{
				// By-name param using a registered var
				$args[] = $vars['registeredVars'][$k];
			}
			else
			{
				// Unknown param
				$args[] = null;
			}
		}

		return call_user_func_array($callback, $args);
	}

	/**
	* Filter the attributes of given tag
	*
	* @private
	*
	* @param  Tag    $tag            Tag being checked
	* @param  array  $tagConfig      Tag's config
	* @param  array  $registeredVars Array of registered vars for use in attribute filters
	* @param  Logger $logger         This parser's Logger instance
	* @return bool                   Whether the whole attribute set is valid
	*/
	public static function filterAttributes(Tag $tag, array $tagConfig, array $registeredVars, Logger $logger)
	{
		if (empty($tagConfig['attributes']))
		{
			$tag->setAttributes([]);

			return true;
		}

		// Generate values for attributes with a generator set
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
		{
			if (isset($attrConfig['generator']))
			{
				$tag->setAttribute(
					$attrName,
					self::executeFilter(
						$attrConfig['generator'],
						[
							'attrName'       => $attrName,
							'logger'         => $logger,
							'registeredVars' => $registeredVars
						]
					)
				);
			}
		}

		// Filter and remove invalid attributes
		foreach ($tag->getAttributes() as $attrName => $attrValue)
		{
			// Test whether this attribute exists and remove it if it doesn't
			if (!isset($tagConfig['attributes'][$attrName]))
			{
				$tag->removeAttribute($attrName);
				continue;
			}

			$attrConfig = $tagConfig['attributes'][$attrName];

			// Test whether this attribute has a filterChain
			if (!isset($attrConfig['filterChain']))
			{
				continue;
			}

			// Record the name of the attribute being filtered into the logger
			$logger->setAttribute($attrName);

			foreach ($attrConfig['filterChain'] as $filter)
			{
				$attrValue = self::executeFilter(
					$filter,
					[
						'attrName'       => $attrName,
						'attrValue'      => $attrValue,
						'logger'         => $logger,
						'registeredVars' => $registeredVars
					]
				);

				if ($attrValue === false)
				{
					$tag->removeAttribute($attrName);
					break;
				}
			}

			// Update the attribute value if it's valid
			if ($attrValue !== false)
			{
				$tag->setAttribute($attrName, $attrValue);
			}

			// Remove the attribute's name from the logger
			$logger->unsetAttribute();
		}

		// Iterate over the attribute definitions to handle missing attributes
		foreach ($tagConfig['attributes'] as $attrName => $attrConfig)
		{
			// Test whether this attribute is missing
			if (!$tag->hasAttribute($attrName))
			{
				if (isset($attrConfig['defaultValue']))
				{
					// Use the attribute's default value
					$tag->setAttribute($attrName, $attrConfig['defaultValue']);
				}
				elseif (!empty($attrConfig['required']))
				{
					// This attribute is missing, has no default value and is required, which means
					// the attribute set is invalid
					return false;
				}
			}
		}

		return true;
	}

	/**
	* Execute given tag's filterChain
	*
	* @param  Tag  $tag Tag to filter
	* @return bool      Whether the tag is valid
	*/
	protected function filterTag(Tag $tag)
	{
		$tagName   = $tag->getName();
		$tagConfig = $this->tagsConfig[$tagName];
		$isValid   = true;

		if (!empty($tagConfig['filterChain']))
		{
			// Record the tag being processed into the logger it can be added to the context of
			// messages logged during the execution
			$this->logger->setTag($tag);

			// Prepare the variables that are accessible to filters
			$vars = [
				'logger'         => $this->logger,
				'openTags'       => $this->openTags,
				'parser'         => $this,
				'registeredVars' => $this->registeredVars,
				'tag'            => $tag,
				'tagConfig'      => $tagConfig
			];

			foreach ($tagConfig['filterChain'] as $filter)
			{
				if (!self::executeFilter($filter, $vars))
				{
					$isValid = false;
					break;
				}
			}

			// Remove the tag from the logger
			$this->logger->unsetTag();
		}

		return $isValid;
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
			$this->output = preg_replace(
				'#<([\\w:]+)[^>]*></\\1>#',
				'',
				$this->output,
				-1,
				$cnt
			);
		}
		while ($cnt);

		// Merge consecutive <i> tags
		if (strpos($this->output, '</i><i>') !== false)
		{
			$this->output = str_replace('</i><i>', '', $this->output);
		}

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

		if ($tagFlags & self::RULE_TRIM_WHITESPACE)
		{
			$skipBefore = ($tag->isStartTag()) ? 2 : 1;
			$skipAfter  = ($tag->isEndTag())   ? 2 : 1;
		}
		else
		{
			$skipBefore = $skipAfter = 0;
		}

		// Current paragraph must end before the tag if:
		//  - the tag is a start (or self-closing) tag and it breaks paragraphs, or
		//  - the tag is an end tag (but not self-closing)
		$closeParagraph = false;
		if ($tag->isStartTag())
		{
			if ($tagFlags & self::RULE_BREAK_PARAGRAPH)
			{
				$closeParagraph = true;
			}
		}
		else
		{
			$closeParagraph = true;
		}

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
				$this->output .= ' ' . $attrName . '="' . htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8') . '"';
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
				$catchupText = '<i>' . $catchupText . '</i>';
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
			if (!($this->context['flags'] & self::RULE_NO_BR_CHILD))
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
	* Execute all the plugins
	*
	* @return void
	*/
	protected function executePluginParsers()
	{
		foreach ($this->pluginsConfig as $pluginName => $pluginConfig)
		{
			if (!empty($pluginConfig['isDisabled']))
			{
				continue;
			}

			if (isset($pluginConfig['quickMatch'])
			 && strpos($this->text, $pluginConfig['quickMatch']) === false)
			{
				continue;
			}

			$matches = [];

			if (isset($pluginConfig['regexp']))
			{
				$cnt = preg_match_all(
					$pluginConfig['regexp'],
					$this->text,
					$matches,
					PREG_SET_ORDER | PREG_OFFSET_CAPTURE
				);

				if (!$cnt)
				{
					continue;
				}

				if ($cnt > $pluginConfig['regexpLimit'])
				{
					if ($pluginConfig['regexpLimitAction'] === 'abort')
					{
						throw new RuntimeException($pluginName . ' limit exceeded');
					}

					$matches = array_slice($matches, 0, $pluginConfig['regexpLimit']);

					$msg = 'Regexp limit exceeded. Only the allowed number of matches will be processed';
					$context = [
						'pluginName' => $pluginName,
						'limit'      => $pluginConfig['regexpLimit']
					];

					if ($pluginConfig['regexpLimitAction'] === 'warn')
					{
						$this->logger->warn($msg, $context);
					}
				}
			}

			// Cache a new instance of this plugin's parser if there isn't one already
			if (!isset($this->pluginParsers[$pluginName]))
			{
				$className = (isset($pluginConfig['className']))
				           ? $pluginConfig['className']
				           : 's9e\\TextFormatter\\Plugins\\' . $pluginName . '\\Parser';

				// Register the parser as a callback
				$this->pluginParsers[$pluginName] = [
					new $className($this, $pluginConfig),
					'parse'
				];
			}

			// Execute the plugin's parser, which will add tags via $this->addStartTag() and others
			call_user_func($this->pluginParsers[$pluginName], $this->text, $matches);
		}
	}

	/**
	* Register a parser
	*
	* Can be used to add a new parser with no plugin config, or pre-generate a parser for an
	* existing plugin
	*
	* @param  string   $pluginName
	* @param  callback $parser
	* @return void
	*/
	public function registerParser($pluginName, $parser)
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

		$this->pluginParsers[$pluginName] = $parser;
	}
}