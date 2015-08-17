<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown;

use s9e\TextFormatter\Parser as Rules;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* @var bool Whether current text contains escape characters
	*/
	protected $hasEscapedChars;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$this->init($text);

		// Match block-level markup as well as forced line breaks
		$this->matchBlockLevelMarkup();

		// Inline code must be done first to avoid false positives in other markup
		$this->matchInlineCode();

		// Images must be matched before links
		$this->matchImages();

		// Do the rest of inline markup
		$this->matchInlineLinks();
		$this->matchStrikethrough();
		$this->matchSuperscript();
		$this->matchEmphasis();
		$this->matchForcedLineBreaks();

		// Unset the text to free its memory
		unset($this->text);
	}

	/**
	* Close a list at given offset
	*
	* @param  array   $list
	* @param  integer $textBoundary
	* @return void
	*/
	protected function closeList(array $list, $textBoundary)
	{
		$this->parser->addEndTag('LIST', $textBoundary, 0)->pairWith($list['listTag']);
		$this->parser->addEndTag('LI',   $textBoundary, 0)->pairWith($list['itemTag']);

		if ($list['tight'])
		{
			foreach ($list['itemTags'] as $itemTag)
			{
				$itemTag->removeFlags(Rules::RULE_CREATE_PARAGRAPHS);
			}
		}
	}

	/**
	* Decode a chunk of encoded text to be used as an attribute value
	*
	* Decodes escaped literals and removes slashes and 0x1A characters
	*
	* @param  string $str Encoded text
	* @return string      Decoded text
	*/
	protected function decode($str)
	{
		$str = stripslashes(str_replace("\x1A", '', $str));

		if ($this->hasEscapedChars)
		{
			$str = strtr(
				$str,
				[
					"\x1B0" => '!', "\x1B1" => '"', "\x1B2" => ')',
					"\x1B3" => '*', "\x1B4" => '[', "\x1B5" => '\\',
					"\x1B6" => ']', "\x1B7" => '^', "\x1B8" => '_',
					"\x1B9" => '`', "\x1BA" => '~'
				]
			);
		}

		return $str;
	}

	/**
	* Encode escaped literals that have a special meaning
	*
	* @param  string $str Original text
	* @return string      Encoded text
	*/
	protected function encode($str)
	{
		return strtr(
			$str,
			[
				'\\!' => "\x1B0", '\\"' => "\x1B1", '\\)'  => "\x1B2",
				'\\*' => "\x1B3", '\\[' => "\x1B4", '\\\\' => "\x1B5",
				'\\]' => "\x1B6", '\\^' => "\x1B7", '\\_'  => "\x1B8",
				'\\`' => "\x1B9", '\\~' => "\x1BA"
			]
		);
	}

	/**
	* Return the length of the markup at the end of an ATX header
	*
	* @param  integer $startPos Start of the header's text
	* @param  integer $endPos   End of the header's text
	* @return integer
	*/
	protected function getAtxHeaderEndTagLen($startPos, $endPos)
	{
		$content = substr($this->text, $startPos, $endPos - $startPos);
		preg_match('/[ \\t]*#*[ \\t]*$/', $content, $m);

		return strlen($m[0]);
	}

	/**
	* Capture lines that contain a Setext-tyle header
	*
	* @return array
	*/
	protected function getSetextLines()
	{
		$setextLines = [];

		if (strpos($this->text, '-') === false && strpos($this->text, '=') === false)
		{
			return $setextLines;
		}

		// Capture the any series of - or = alone on a line, optionally preceded with the
		// angle brackets notation used in blockquotes
		$regexp = '/^(?=[-=>])(?:> ?)*(?=[-=])(?:-+|=+) *$/m';
		if (preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE))
		{
			foreach ($matches[0] as list($match, $matchPos))
			{
				// Compute the position of the end tag. We start on the LF character before the
				// match and keep rewinding until we find a non-space character
				$endTagPos = $matchPos - 1;
				while ($endTagPos > 0 && $this->text[$endTagPos - 1] === ' ')
				{
					--$endTagPos;
				}

				// Store at the offset of the LF character
				$setextLines[$matchPos - 1] = [
					'endTagLen'  => $matchPos + strlen($match) - $endTagPos,
					'endTagPos'  => $endTagPos,
					'quoteDepth' => substr_count($match, '>'),
					'tagName'    => ($match[0] === '=') ? 'H1' : 'H2'
				];
			}
		}

		return $setextLines;
	}

	/**
	* Get emphasis markup split by block
	*
	* @param  string  $regexp Regexp used to match emphasis
	* @param  integer $pos    Position in the text of the first emphasis character
	* @return array[]         Each array contains a list of [matchPos, matchLen] pairs
	*/
	protected function getEmphasisByBlock($regexp, $pos)
	{
		$block    = [];
		$blocks   = [];
		$breakPos = strpos($this->text, "\x17", $pos);

		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE, $pos);
		foreach ($matches[0] as $m)
		{
			$matchPos = $m[1];
			$matchLen = strlen($m[0]);

			// Test whether we've just passed the limits of a block
			if ($matchPos > $breakPos)
			{
				$blocks[] = $block;
				$block    = [];
				$breakPos = strpos($this->text, "\x17", $matchPos);
			}

			// Test whether we should ignore this markup
			if (!$this->ignoreEmphasis($matchPos, $matchLen))
			{
				$block[] = [$matchPos, $matchLen];
			}
		}
		$blocks[] = $block;

		return $blocks;
	}

	/**
	* Test whether emphasis should be ignored at the given position in the text
	*
	* @param  integer $matchPos Position of the emphasis in the text
	* @param  integer $matchLen Length of the emphasis
	* @return bool
	*/
	protected function ignoreEmphasis($matchPos, $matchLen)
	{
		// Ignore single underscores between alphanumeric characters
		if ($this->text[$matchPos] === '_' && $matchLen === 1 && $this->isSurroundedByAlnum($matchPos, $matchLen))
		{
			return true;
		}

		return false;
	}

	/**
	* Initialize this parser with given text
	*
	* @param  string $text Text to be parsed
	* @return void
	*/
	protected function init($text)
	{
		if (strpos($text, '\\') === false || !preg_match('/\\\\[!")*[\\\\\\]^_`~]/', $text))
		{
			$this->hasEscapedChars = false;
		}
		else
		{
			$this->hasEscapedChars = true;

			// Encode escaped literals that have a special meaning otherwise, so that we don't have
			// to take them into account in regexps
			$text = $this->encode($text);
		}

		// We append a couple of lines and a non-whitespace character at the end of the text in
		// order to trigger the closure of all open blocks such as quotes and lists
		$text .= "\n\n\x17";

		$this->text = $text;
	}

	/**
	* Test whether given character is alphanumeric
	*
	* @param  string $chr
	* @return bool
	*/
	protected function isAlnum($chr)
	{
		return (strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $chr) > 0);
	}

	/**
	* Test whether a length of text is surrounded by alphanumeric characters
	*
	* @param  integer $matchPos Start of the text
	* @param  integer $matchLen Length of the text
	* @return bool
	*/
	protected function isSurroundedByAlnum($matchPos, $matchLen)
	{
		return ($matchPos > 0 && $this->isAlnum($this->text[$matchPos - 1]) && $this->isAlnum($this->text[$matchPos + $matchLen]));
	}

	/**
	* Match block-level markup, as well as forced line breaks and headers
	*
	* @return void
	*/
	protected function matchBlockLevelMarkup()
	{
		$boundaries   = [];
		$codeFence    = null;
		$codeIndent   = 4;
		$codeTag      = null;
		$lineIsEmpty  = true;
		$lists        = [];
		$listsCnt     = 0;
		$newContext   = false;
		$quotes       = [];
		$quotesCnt    = 0;
		$setextLines  = $this->getSetextLines();
		$textBoundary = 0;

		$regexp = '/^(?:(?=[-*+\\d \\t>`~#_])((?: {0,3}> ?)+)?([ \\t]+)?(\\* *\\* *\\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$|=+$)?((?:[-*+]|\\d+\\.)[ \\t]+(?=\\S))?[ \\t]*(#{1,6}[ \\t]+|```+.*|~~~+.*)?)?/m';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		foreach ($matches as $m)
		{
			$matchPos  = $m[0][1];
			$matchLen  = strlen($m[0][0]);
			$ignoreLen = 0;

			// If the last line was empty then this is not a continuation, and vice-versa
			$continuation = !$lineIsEmpty;

			// Capture the position of the end of the line and determine whether the line is empty
			$lfPos       = strpos($this->text, "\n", $matchPos);
			$lineIsEmpty = ($lfPos === $matchPos + $matchLen && empty($m[3][0]) && empty($m[4][0]) && empty($m[5][0]));

			// If the line is empty and it's the first empty line then we break current paragraph.
			$breakParagraph = ($lineIsEmpty && $continuation);

			// Count quote marks
			if (!empty($m[1][0]))
			{
				$quoteDepth = substr_count($m[1][0], '>');
				$ignoreLen  = strlen($m[1][0]);
			}
			else
			{
				$quoteDepth = 0;
			}

			// Close supernumerary quotes
			if ($quoteDepth < $quotesCnt && !$continuation && !$lineIsEmpty)
			{
				$newContext = true;

				do
				{
					$this->parser->addEndTag('QUOTE', $textBoundary, 0)
					             ->pairWith(array_pop($quotes));
				}
				while ($quoteDepth < --$quotesCnt);
			}

			// Open new quotes
			if ($quoteDepth > $quotesCnt && !$lineIsEmpty)
			{
				$newContext = true;

				do
				{
					$tag = $this->parser->addStartTag('QUOTE', $matchPos, 0);
					$tag->setSortPriority($quotesCnt);

					$quotes[] = $tag;
				}
				while ($quoteDepth > ++$quotesCnt);
			}

			// Compute the width of the indentation
			$indentWidth = 0;
			$indentPos   = 0;
			if (!empty($m[2][0]))
			{
				$indentStr = $m[2][0];
				$indentLen = strlen($indentStr);

				do
				{
					if ($indentStr[$indentPos] === ' ')
					{
						++$indentWidth;
					}
					else
					{
						$indentWidth = ($indentWidth + 4) & ~3;
					}
				}
				while (++$indentPos < $indentLen && $indentWidth < $codeIndent);
			}

			// Test whether we're out of a code block
			if (isset($codeTag) && !$codeFence && $indentWidth < $codeIndent && !$lineIsEmpty)
			{
				$newContext = true;
			}

			if ($newContext)
			{
				$newContext = false;

				// Close the code block if applicable
				if (isset($codeTag))
				{
					// Overwrite the whole block
					$this->overwrite($codeTag->getPos(), $textBoundary - $codeTag->getPos());

					$endTag = $this->parser->addEndTag('CODE', $textBoundary, 0);
					$endTag->pairWith($codeTag);
					$endTag->setSortPriority(-1);
					$codeTag = null;
					$codeFence = null;
				}

				// Close all the lists
				foreach ($lists as $list)
				{
					$this->closeList($list, $textBoundary);
				}
				$lists    = [];
				$listsCnt = 0;

				// Mark the block boundary
				if ($matchPos)
				{
					$boundaries[] = $matchPos - 1;
				}
			}

			if ($indentWidth >= $codeIndent)
			{
				if (isset($codeTag) || !$continuation)
				{
					// Adjust the amount of text being ignored
					$ignoreLen += $indentPos;

					if (!isset($codeTag))
					{
						// Create code block
						$codeTag = $this->parser->addStartTag('CODE', $matchPos + $ignoreLen, 0);
					}

					// Clear the captures to prevent any further processing
					$m = [];
				}
			}
			else
			{
				$hasListItem = !empty($m[4][0]);

				if (!$indentWidth && !$continuation && !$hasListItem && !$lineIsEmpty)
				{
					// Start of a new paragraph
					$listIndex = -1;
				}
				elseif ($continuation && !$hasListItem)
				{
					// Continuation of current list item or paragraph
					$listIndex = $listsCnt - 1;
				}
				elseif (!$listsCnt)
				{
					// We're not inside of a list already, we can start one if there's a list item
					// and it's not in continuation of a paragraph
					if (!$continuation && $hasListItem)
					{
						// Start of a new list
						$listIndex = 0;
					}
					else
					{
						// We're in a normal paragraph
						$listIndex = -1;
					}
				}
				else
				{
					// We're inside of a list but we need to compute the depth
					$listIndex = 0;
					while ($listIndex < $listsCnt && $indentWidth > $lists[$listIndex]['maxIndent'])
					{
						++$listIndex;
					}
				}

				// Close deeper lists
				while ($listIndex < $listsCnt - 1)
				{
					$this->closeList(array_pop($lists), $textBoundary);
					--$listsCnt;
				}

				// If there's no list item at current index, we'll need to either create one or
				// drop down to previous index, in which case we have to adjust maxIndent
				if ($listIndex === $listsCnt && !$hasListItem)
				{
					--$listIndex;
				}

				if ($hasListItem && $listIndex >= 0)
				{
					$breakParagraph = true;

					// Compute the position and amount of text consumed by the item tag
					$tagPos = $matchPos + $ignoreLen + $indentPos;
					$tagLen = strlen($m[4][0]);

					// Create a LI tag that consumes its markup
					$itemTag = $this->parser->addStartTag('LI', $tagPos, $tagLen);

					// Overwrite the markup
					$this->overwrite($tagPos, $tagLen);

					// If the list index is within current lists count it means this is not a new
					// list and we have to close the last item. Otherwise, it's a new list that we
					// have to create
					if ($listIndex < $listsCnt)
					{
						$this->parser->addEndTag('LI', $textBoundary, 0)
						             ->pairWith($lists[$listIndex]['itemTag']);

						// Record the item in the list
						$lists[$listIndex]['itemTag']    = $itemTag;
						$lists[$listIndex]['itemTags'][] = $itemTag;
					}
					else
					{
						++$listsCnt;

						if ($listIndex)
						{
							$minIndent = $lists[$listIndex - 1]['maxIndent'] + 1;
							$maxIndent = max($minIndent, $listIndex * 4);
						}
						else
						{
							$minIndent = 0;
							$maxIndent = $indentWidth;
						}

						// Create a 0-width LIST tag right before the item tag LI
						$listTag = $this->parser->addStartTag('LIST', $tagPos, 0);

						// Test whether the list item ends with a dot, as in "1."
						if (strpos($m[4][0], '.') !== false)
						{
							$listTag->setAttribute('type', 'decimal');
						}

						// Record the new list depth
						$lists[] = [
							'listTag'   => $listTag,
							'itemTag'   => $itemTag,
							'itemTags'  => [$itemTag],
							'minIndent' => $minIndent,
							'maxIndent' => $maxIndent,
							'tight'     => true
						];
					}
				}

				// If we're in a list, on a non-empty line preceded with a blank line...
				if ($listsCnt && !$continuation && !$lineIsEmpty)
				{
					// ...and this is not the first item of the list...
					if (count($lists[0]['itemTags']) > 1 || !$hasListItem)
					{
						// ...every list that is currently open becomes loose
						foreach ($lists as &$list)
						{
							$list['tight'] = false;
						}
						unset($list);
					}
				}

				$codeIndent = ($listsCnt + 1) * 4;
			}

			if (isset($m[5]))
			{
				// Headers
				if ($m[5][0][0] === '#')
				{
					$startTagLen = strlen($m[5][0]);
					$startTagPos = $matchPos + $matchLen - $startTagLen;
					$endTagLen   = $this->getAtxHeaderEndTagLen($matchPos + $matchLen, $lfPos);
					$endTagPos   = $lfPos - $endTagLen;

					$this->parser->addTagPair('H' . strspn($m[5][0], '#', 0, 6), $startTagPos, $startTagLen, $endTagPos, $endTagLen);

					// Mark the start and the end of the header as boundaries
					$boundaries[] = $startTagPos;
					$boundaries[] = $endTagPos;

					if ($continuation)
					{
						$breakParagraph = true;
					}
				}
				// Code fence
				elseif ($m[5][0][0] === '`' || $m[5][0][0] === '~')
				{
					$tagPos = $matchPos + $ignoreLen;
					$tagLen = $lfPos - $tagPos;

					if (isset($codeTag) && $m[5][0][0] === $codeFence)
					{
						$endTag = $this->parser->addEndTag('CODE', $tagPos, $tagLen);
						$endTag->pairWith($codeTag);
						$endTag->setSortPriority(-1);

						$this->parser->addIgnoreTag($textBoundary, $tagPos - $textBoundary);

						// Overwrite the whole block
						$this->overwrite($codeTag->getPos(), $tagPos + $tagLen - $codeTag->getPos());
						$codeTag = null;
						$codeFence = null;
					}
					elseif (!isset($codeTag))
					{
						// Create code block
						$codeTag   = $this->parser->addStartTag('CODE', $tagPos, $tagLen);
						$codeFence = $m[5][0][0];

						// Ignore the next character, which should be a newline
						$this->parser->addIgnoreTag($tagPos + $tagLen, 1);

						// Add the language if present, e.g. ```php
						$lang = ltrim($m[5][0], '`~');
						if ($lang !== '')
						{
							$codeTag->setAttribute('lang', $lang);
						}
					}
				}
			}
			elseif (!empty($m[3][0]) && !$listsCnt)
			{
				// Horizontal rule
				$this->parser->addSelfClosingTag('HR', $matchPos + $ignoreLen, $matchLen - $ignoreLen);
				$breakParagraph = true;

				// Overwrite the LF to prevent forced line breaks from matching
				$this->overwrite($lfPos, 1);
			}
			elseif (isset($setextLines[$lfPos]) && $setextLines[$lfPos]['quoteDepth'] === $quoteDepth && !$lineIsEmpty && !$listsCnt && !isset($codeTag))
			{
				// Setext-style header
				$this->parser->addTagPair(
					$setextLines[$lfPos]['tagName'],
					$matchPos + $ignoreLen,
					0,
					$setextLines[$lfPos]['endTagPos'],
					$setextLines[$lfPos]['endTagLen']
				);

				// Overwrite the LF to prevent forced line breaks from matching
				$this->overwrite($lfPos, 1);
			}

			if ($breakParagraph)
			{
				$this->parser->addParagraphBreak($textBoundary);
				$boundaries[] = $textBoundary;
			}

			if (!$lineIsEmpty)
			{
				$textBoundary = $lfPos;
			}

			if ($ignoreLen)
			{
				$this->parser->addIgnoreTag($matchPos, $ignoreLen)->setSortPriority(1000);
			}
		}

		foreach ($boundaries as $pos)
		{
			$this->text[$pos] = "\x17";
		}
	}

	/**
	* Match all forms of emphasis (emphasis and strong, using underscores or asterisks)
	*
	* @return void
	*/
	protected function matchEmphasis()
	{
		$this->matchEmphasisByCharacter('*', '/\\*+/');
		$this->matchEmphasisByCharacter('_', '/_+/');
	}

	/**
	* Match emphasis and strong applied using given character
	*
	* @param  string $character Markup character, either * or _
	* @param  string $regexp    Regexp used to match the series of emphasis character
	* @return void
	*/
	protected function matchEmphasisByCharacter($character, $regexp)
	{
		$pos = strpos($this->text, $character);
		if ($pos === false)
		{
			return;
		}

		foreach ($this->getEmphasisByBlock($regexp, $pos) as $block)
		{
			$this->processEmphasisBlock($block);
		}
	}

	/**
	* Match forced line breaks
	*
	* @return void
	*/
	protected function matchForcedLineBreaks()
	{
		$pos = strpos($this->text, "  \n");
		while ($pos !== false)
		{
			$this->parser->addBrTag($pos + 2);
			$pos = strpos($this->text, "  \n", $pos + 3);
		}
	}

	/**
	* Match images markup
	*
	* @return void
	*/
	protected function matchImages()
	{
		$pos = strpos($this->text, '![');
		if ($pos === false)
		{
			return;
		}

		preg_match_all(
			'/!\\[([^\\x17\\]]*+)] ?\\(([^\\x17 ")]++)(?> "([^\\x17"]*+)")?\\)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER,
			$pos
		);

		foreach ($matches as $m)
		{
			$matchPos    = $m[0][1];
			$matchLen    = strlen($m[0][0]);
			$contentLen  = strlen($m[1][0]);
			$startTagPos = $matchPos;
			$startTagLen = 2;
			$endTagPos   = $startTagPos + $startTagLen + $contentLen;
			$endTagLen   = $matchLen - $startTagLen - $contentLen;

			$startTag = $this->parser->addTagPair('IMG', $startTagPos, $startTagLen, $endTagPos, $endTagLen);
			$startTag->setAttribute('alt', $this->decode($m[1][0]));
			$startTag->setAttribute('src', $this->decode($m[2][0]));

			if (isset($m[3]))
			{
				$startTag->setAttribute('title', $this->decode($m[3][0]));
			}

			// Overwrite the markup
			$this->overwrite($matchPos, $matchLen);
		}
	}

	/**
	* Match inline code
	*
	* @return void
	*/
	protected function matchInlineCode()
	{
		$pos = strpos($this->text, '`');
		if ($pos === false)
		{
			return;
		}

		preg_match_all(
			'/(``?)[^\\x17]*?[^`]\\1(?!`)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER,
			$pos
		);

		foreach ($matches as $m)
		{
			$matchLen = strlen($m[0][0]);
			$matchPos = $m[0][1];
			$tagLen   = strlen($m[1][0]);

			$this->parser->addTagPair('C', $matchPos, $tagLen, $matchPos + $matchLen - $tagLen, $tagLen);

			// Overwrite the markup
			$this->overwrite($matchPos, $matchLen);
		}
	}

	/**
	* Match inline links
	*
	* @return void
	*/
	protected function matchInlineLinks()
	{
		$pos = strpos($this->text, '[');
		if ($pos === false)
		{
			return;
		}

		preg_match_all(
			'/\\[([^\\x17\\]]+)] ?\\(([^\\x17 ()]+(?:\\([^\\x17 ()]+\\)[^\\x17 ()]*)*[^\\x17 )]*)(?: "(.*?)")?\\)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER,
			$pos
		);
		foreach ($matches as $m)
		{
			$matchPos    = $m[0][1];
			$matchLen    = strlen($m[0][0]);
			$contentLen  = strlen($m[1][0]);
			$startTagPos = $matchPos;
			$startTagLen = 1;
			$endTagPos   = $startTagPos + $startTagLen + $contentLen;
			$endTagLen   = $matchLen - $startTagLen - $contentLen;

			$tag = $this->parser->addTagPair('URL', $startTagPos, $startTagLen, $endTagPos, $endTagLen);
			$tag->setAttribute('url', $this->decode($m[2][0]));

			if (isset($m[3]) && $m[3][0] !== '')
			{
				$tag->setAttribute('title', $this->decode($m[3][0]));
			}

			// Overwrite the markup without touching the link's text
			$this->overwrite($startTagPos, $startTagLen);
			$this->overwrite($endTagPos,   $endTagLen);
		}
	}

	/**
	* Match strikethrough
	*
	* @return void
	*/
	protected function matchStrikethrough()
	{
		$pos = strpos($this->text, '~~');
		if ($pos === false)
		{
			return;
		}

		preg_match_all(
			'/~~[^\\x17]+?~~/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE,
			$pos
		);

		foreach ($matches[0] as list($match, $matchPos))
		{
			$matchLen = strlen($match);

			$this->parser->addTagPair('DEL', $matchPos, 2, $matchPos + $matchLen - 2, 2);
		}
	}

	/**
	* Match superscript
	*
	* @return void
	*/
	protected function matchSuperscript()
	{
		$pos = strpos($this->text, '^');
		if ($pos === false)
		{
			return;
		}

		preg_match_all(
			'/\\^[^\\x17\\s]++/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE,
			$pos
		);

		foreach ($matches[0] as list($match, $matchPos))
		{
			$matchLen    = strlen($match);
			$startTagPos = $matchPos;
			$endTagPos   = $matchPos + $matchLen;

			$parts = explode('^', $match);
			unset($parts[0]);

			foreach ($parts as $part)
			{
				$this->parser->addTagPair('SUP', $startTagPos, 1, $endTagPos, 0);
				$startTagPos += 1 + strlen($part);
			}
		}
	}

	/**
	* Overwrite part of the text with substitution characters ^Z (0x1A)
	*
	* @param  integer $pos Start of the range
	* @param  integer $len Length of text to overwrite
	* @return void
	*/
	protected function overwrite($pos, $len)
	{
		$this->text = substr($this->text, 0, $pos) . str_repeat("\x1A", $len) . substr($this->text, $pos + $len);
	}

	/**
	* Process a list of emphasis markup strings
	*
	* @param  array[] $block List of [matchPos, matchLen] pairs
	* @return void
	*/
	protected function processEmphasisBlock(array $block)
	{
		$buffered  = 0;
		$emPos     = -1;
		$strongPos = -1;
		foreach ($block as list($matchPos, $matchLen))
		{
			$closeLen     = min(3, $matchLen);
			$closeEm      = $closeLen & $buffered & 1;
			$closeStrong  = $closeLen & $buffered & 2;
			$emEndPos     = $matchPos;
			$strongEndPos = $matchPos;

			if ($buffered > 2 && $emPos === $strongPos)
			{
				if ($closeEm)
				{
					$emPos += 2;
				}
				else
				{
					++$strongPos;
				}
			}

			if ($closeEm && $closeStrong)
			{
				if ($emPos < $strongPos)
				{
					$emEndPos += 2;
				}
				else
				{
					++$strongEndPos;
				}
			}

			$remaining = $matchLen;
			if ($closeEm)
			{
				--$buffered;
				--$remaining;
				$this->parser->addTagPair('EM', $emPos, 1, $emEndPos, 1);
			}
			if ($closeStrong)
			{
				$buffered  -= 2;
				$remaining -= 2;
				$this->parser->addTagPair('STRONG', $strongPos, 2, $strongEndPos, 2);
			}

			$remaining = min(3, $remaining);
			if ($remaining & 1)
			{
				$emPos = $matchPos + $matchLen - $remaining;
			}
			if ($remaining & 2)
			{
				$strongPos = $matchPos + $matchLen - $remaining;
			}
			$buffered += $remaining;
		}
	}
}