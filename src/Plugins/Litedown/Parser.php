<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown;

use s9e\TextFormatter\Parser as Rules;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		if (strpos($text, '\\') === false || !preg_match('/\\\\[!")*[\\\\\\]^_`~]/', $text))
		{
			$hasEscapedChars = false;
		}
		else
		{
			$hasEscapedChars = true;

			// Encode escaped literals that have a special meaning otherwise, so that we don't have
			// to take them into account in regexps
			$text = strtr(
				$text,
				[
					'\\!'  => "\x1B0",
					'\\"'  => "\x1B1",
					'\\)'  => "\x1B2",
					'\\*'  => "\x1B3",
					'\\['  => "\x1B4",
					'\\\\' => "\x1B5",
					'\\]'  => "\x1B6",
					'\\^'  => "\x1B7",
					'\\_'  => "\x1B8",
					'\\`'  => "\x1B9",
					'\\~'  => "\x1BA"
				]
			);
		}

		// We append a couple of lines and a non-whitespace character at the end of the text in
		// order to trigger the closure of all open blocks such as quotes and lists
		$text .= "\n\n\x17";

		$boundaries   = [];
		$codeIndent   = 4;
		$codeTag      = null;
		$lineIsEmpty  = true;
		$lists        = [];
		$listsCnt     = 0;
		$newContext   = false;
		$quotes       = [];
		$quotesCnt    = 0;
		$textBoundary = 0;

		$regexp = '/^(?:(?=[-*+\\d \\t>`#_])((?: {0,3}> ?)+)?([ \\t]+)?(\\* *\\* *\\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$)?((?:[-*+]|\\d+\\.)[ \\t]+(?=.))?[ \\t]*(#+[ \\t]*(?=.)|```+)?)?/m';
		preg_match_all($regexp, $text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		foreach ($matches as $m)
		{
			$matchPos  = $m[0][1];
			$matchLen  = strlen($m[0][0]);
			$ignoreLen = 0;

			// If the last line was empty then this is not a continuation, and vice-versa
			$continuation = !$lineIsEmpty;

			// Capture the position of the end of the line and determine whether the line is empty
			$lfPos       = strpos($text, "\n", $matchPos);
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
			if ($indentWidth < $codeIndent && isset($codeTag) && !$lineIsEmpty)
			{
				$newContext = true;
			}

			if ($newContext)
			{
				$newContext = false;

				// Close the code block if applicable
				if (isset($codeTag))
				{
					$endTag = $this->parser->addEndTag('CODE', $textBoundary, 0);
					$endTag->pairWith($codeTag);
					$endTag->setSortPriority(-1);
					$codeTag = null;
				}

				// Close all the lists
				foreach ($lists as $list)
				{
					$this->parser->addEndTag('LIST', $textBoundary, 0)->pairWith($list['listTag']);
					$this->parser->addEndTag('LI',   $textBoundary, 0)->pairWith($list['itemTag']);
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
					$list = array_pop($lists);
					--$listsCnt;

					$this->parser->addEndTag('LIST', $textBoundary, 0)->pairWith($list['listTag']);
					$this->parser->addEndTag('LI',   $textBoundary, 0)->pairWith($list['itemTag']);
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
					$itemTag->removeFlags(Rules::RULE_CREATE_PARAGRAPHS);

					// Overwrite the markup
					self::overwrite($text, $tagPos, $tagLen);

					// If the list index is within current lists count it means this is not a new
					// list and we have to close the last item. Otherwise, it's a new list that we
					// have to create
					if ($listIndex < $listsCnt)
					{
						$this->parser->addEndTag('LI', $textBoundary, 0)
						             ->pairWith($lists[$listIndex]['itemTag']);
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
							'minIndent' => $minIndent,
							'maxIndent' => $maxIndent
						];
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
					while (strpos(" #\t", $text[$endTagPos - 1]) !== false)
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
			}
			elseif (!empty($m[3][0]) && !$listsCnt)
			{
				// Horizontal rule
				$this->parser->addSelfClosingTag('HR', $matchPos + $ignoreLen, $matchLen - $ignoreLen);
				$breakParagraph = true;
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
			$text[$pos] = "\x17";
		}

		// Inline code
		if (strpos($text, '`') !== false)
		{
			preg_match_all(
				'/(``?)[^\\x17]*?[^`]\\1(?!`)/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE | PREG_SET_ORDER
			);

			foreach ($matches as $m)
			{
				$matchLen = strlen($m[0][0]);
				$matchPos = $m[0][1];
				$tagLen   = strlen($m[1][0]);

				$this->parser->addTagPair('C', $matchPos, $tagLen, $matchPos + $matchLen - $tagLen, $tagLen);

				// Overwrite the markup
				self::overwrite($text, $matchPos, $matchLen);
			}
		}

		// Images
		if (strpos($text, '![') !== false)
		{
			preg_match_all(
				'/!\\[([^\\x17\\]]++)] ?\\(([^\\x17 ")]++)(?> "([^\\x17"]*+)")?\\)/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE | PREG_SET_ORDER
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
				$startTag->setAttribute('alt', self::decode($m[1][0], $hasEscapedChars));
				$startTag->setAttribute('src', self::decode($m[2][0], $hasEscapedChars));

				if (isset($m[3]))
				{
					$startTag->setAttribute('title', self::decode($m[3][0], $hasEscapedChars));
				}

				// Overwrite the markup
				self::overwrite($text, $matchPos, $matchLen);
			}
		}

		// Inline links
		if (strpos($text, '[') !== false)
		{
			preg_match_all(
				'/\\[([^\\x17\\]]++)] ?\\(([^\\x17)]++)\\)/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE | PREG_SET_ORDER
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
				$tag->setAttribute('url', self::decode($url, $hasEscapedChars));

				if ($title !== '')
				{
					$tag->setAttribute('title', self::decode($title, $hasEscapedChars));
				}

				// Overwrite the markup without touching the link's text
				self::overwrite($text, $startTagPos, $startTagLen);
				self::overwrite($text, $endTagPos,   $endTagLen);
			}
		}

		// Strikethrough
		if (strpos($text, '~~') !== false)
		{
			preg_match_all(
				'/~~[^\\x17]+?~~/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);

			foreach ($matches[0] as list($match, $matchPos))
			{
				$matchLen = strlen($match);

				$this->parser->addTagPair('DEL', $matchPos, 2, $matchPos + $matchLen - 2, 2);

				// Overwrite the markup
				self::overwrite($text, $matchPos, $matchLen);
			}
		}

		// Superscript
		if (strpos($text, '^') !== false)
		{
			preg_match_all(
				'/\\^[^\\x17\\s]++/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
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

		// Emphasis
		foreach (['*' => '/\\*+/', '_' => '/_+/'] as $c => $regexp)
		{
			if (strpos($text, $c) === false)
			{
				continue;
			}

			$buffered = 0;
			$breakPos = strpos($text, "\x17");

			preg_match_all($regexp, $text, $matches, PREG_OFFSET_CAPTURE);
			foreach ($matches[0] as list($match, $matchPos))
			{
				$matchLen = strlen($match);

				// Test whether we've just passed the limits of a block
				if ($matchPos > $breakPos)
				{
					// Reset the buffer then look for the next break
					$buffered = 0;
					$breakPos = strpos($text, "\x17", $matchPos);
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
					if ($c === '_'
					 && $matchPos > 0
					 && strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $text[$matchPos - 1]) > 0
					 && strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $text[$matchPos + 1]) > 0)
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
	}

	/**
	* Decode a chunk of encoded text to be used as an attribute value
	*
	* Decodes escaped literals and removes slashes and 0x1A characters
	*
	* @param  string $str      Encoded text
	* @param  bool   $unescape Whether to unescape 0x1B sequences
	* @return string           Decoded text
	*/
	protected static function decode($str, $unescape)
	{
		$str = stripslashes(str_replace("\x1A", '', $str));

		if ($unescape)
		{
			$decode = [
				"\x1B0" => '!',
				"\x1B1" => '"',
				"\x1B2" => ')',
				"\x1B3" => '*',
				"\x1B4" => '[',
				"\x1B5" => '\\',
				"\x1B6" => ']',
				"\x1B7" => '^',
				"\x1B8" => '_',
				"\x1B9" => '`',
				"\x1BA" => '~'
			];

			$str = strtr($str, $decode);
		}

		return $str;
	}

	/**
	* Overwrite part of the text with substitution characters ^Z (0x1A)
	*
	* @param  string  $text Original text
	* @param  integer $pos  Start of the range
	* @param  integer $len  Length of text to overwrite
	* @return void
	*/
	protected function overwrite(&$text, $pos, $len)
	{
		$text = substr($text, 0, $pos) . str_repeat("\x1A", $len) . substr($text, $pos + $len);
	}
}