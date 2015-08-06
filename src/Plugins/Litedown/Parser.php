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

		$regexp = '/^(?:(?=[-*+\\d \\t>`~#_])((?: {0,3}> ?)+)?([ \\t]+)?(\\* *\\* *\\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$|=+$)?((?:[-*+]|\\d+\\.)[ \\t]+(?=\\S))?[ \\t]*(#+[ \\t]+(?=\\S)|```+.*|~~~+.*)?)?/m';
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
					$endTagPos   = $lfPos;
					$endTagLen   = 0;

					// Consume the leftmost whitespace and # characters as part of the end tag
					while (strpos(" #\t", $this->text[$endTagPos - 1]) !== false)
					{
						--$endTagPos;
						++$endTagLen;
					}

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
						$this->overwrite($codeTag->getPos(), $tagPos + $tagLen);
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

		$buffered = 0;
		$breakPos = strpos($this->text, "\x17", $pos);

		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE, $pos);
		foreach ($matches[0] as list($match, $matchPos))
		{
			$matchLen = strlen($match);

			// Test whether we've just passed the limits of a block
			if ($matchPos > $breakPos)
			{
				// Reset the buffer then look for the next break
				$buffered = 0;
				$breakPos = strpos($this->text, "\x17", $matchPos);
			}

			if ($matchLen >= 3)
			{
				// Number of characters left unconsumed
				$remaining = $matchLen;

				if ($buffered < 3)
				{
					$strongEndPos = $emEndPos = $matchPos;
				}
				else
				{
					// Determine the order of strong's and em's end tags
					if ($emPos < $strongPos)
					{
						// If em starts before strong, it must end after it
						$strongEndPos = $matchPos;
						$emEndPos     = $matchPos + 2;
					}
					else
					{
						// Make strong end after em
						$strongEndPos = $matchPos + 1;
						$emEndPos     = $matchPos;

						// If the buffer holds three consecutive characters and the order of
						// strong and em is not defined we push em inside of strong
						if ($strongPos === $emPos)
						{
							$emPos += 2;
						}
					}
				}

				// 2 or 3 means a strong is buffered
				// Strong uses the outer characters
				if ($buffered & 2)
				{
					$this->parser->addTagPair('STRONG', $strongPos, 2, $strongEndPos, 2);
					$remaining -= 2;
				}

				// 1 or 3 means an em is buffered
				// Em uses the inner characters
				if ($buffered & 1)
				{
					$this->parser->addTagPair('EM', $emPos, 1, $emEndPos, 1);
					--$remaining;
				}

				if (!$remaining)
				{
					$buffered = 0;
				}
				else
				{
					$buffered = min($remaining, 3);

					if ($buffered & 1)
					{
						$emPos = $matchPos + $matchLen - $buffered;
					}

					if ($buffered & 2)
					{
						$strongPos = $matchPos + $matchLen - $buffered;
					}
				}
			}
			elseif ($matchLen === 2)
			{
				if ($buffered === 3 && $strongPos === $emPos)
				{
					$this->parser->addTagPair('STRONG', $emPos + 1, 2, $matchPos, 2);
					$buffered = 1;
				}
				elseif ($buffered & 2)
				{
					$this->parser->addTagPair('STRONG', $strongPos, 2, $matchPos, 2);
					$buffered -= 2;
				}
				else
				{
					$buffered += 2;
					$strongPos = $matchPos;
				}
			}
			else
			{
				// Ignore single underscores when they are between alphanumeric ASCII chars
				if ($character === '_'
				 && $matchPos > 0
				 && strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $this->text[$matchPos - 1]) > 0
				 && strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $this->text[$matchPos + 1]) > 0)
				{
					 continue;
				}

				if ($buffered === 3 && $strongPos === $emPos)
				{
					$this->parser->addTagPair('EM', $strongPos + 2, 1, $matchPos, 1);
					$buffered = 2;
				}
				elseif ($buffered & 1)
				{
					$this->parser->addTagPair('EM', $emPos, 1, $matchPos, 1);
					--$buffered;
				}
				else
				{
					++$buffered;
					$emPos = $matchPos;
				}
			}
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
			'/\\[([^\\x17\\]]++)] ?\\(([^\\x17)]++)\\)/',
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

			// Split the URL from the title if applicable
			$url   = $m[2][0];
			$title = '';
			if (preg_match('/^(.+?) "(.*?)"$/', $url, $m))
			{
				$url   = $m[1];
				$title = $m[2];
			}

			$tag = $this->parser->addTagPair('URL', $startTagPos, $startTagLen, $endTagPos, $endTagLen);
			$tag->setAttribute('url', $this->decode($url));

			if ($title !== '')
			{
				$tag->setAttribute('title', $this->decode($title));
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
}