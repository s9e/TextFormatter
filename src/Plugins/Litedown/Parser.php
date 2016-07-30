<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown;

use s9e\TextFormatter\Parser as Rules;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* @var bool Whether current text contains escape characters
	*/
	protected $hasEscapedChars;

	/**
	* @var bool Whether current text contains references
	*/
	protected $hasRefs;

	/**
	* @var array Array of [label => link info]
	*/
	protected $refs;

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

		// Capture link references after block markup as been overwritten
		$this->matchLinkReferences();

		// Inline code must be done first to avoid false positives in other inline markup
		$this->matchInlineCode();

		// Do the rest of inline markup. Images must be matched before links
		$this->matchImages();
		$this->matchLinks();
		$this->matchStrikethrough();
		$this->matchSuperscript();
		$this->matchEmphasis();
		$this->matchForcedLineBreaks();

		// Unset the text to free its memory
		unset($this->text);
	}

	/**
	* Add an image tag for given text span
	*
	* @param  integer $startTagPos Start tag position
	* @param  integer $endTagPos   End tag position
	* @param  integer $endTagLen   End tag length
	* @param  string  $linkInfo    URL optionally followed by space and a title
	* @param  string  $alt         Value for the alt attribute
	* @return void
	*/
	protected function addImageTag($startTagPos, $endTagPos, $endTagLen, $linkInfo, $alt)
	{
		$tag = $this->parser->addTagPair('IMG', $startTagPos, 2, $endTagPos, $endTagLen);
		$this->setLinkAttributes($tag, $linkInfo, 'src');
		$tag->setAttribute('alt', $this->decode($alt));

		// Overwrite the markup
		$this->overwrite($startTagPos, $endTagPos + $endTagLen - $startTagPos);
	}

	/**
	* Add the tag pair for an inline code span
	*
	* @param  array $left  Left marker
	* @param  array $right Right marker
	* @return void
	*/
	protected function addInlineCodeTags($left, $right)
	{
		$startTagPos = $left['pos'];
		$startTagLen = $left['len'] + $left['trimAfter'];
		$endTagPos   = $right['pos'] - $right['trimBefore'];
		$endTagLen   = $right['len'] + $right['trimBefore'];
		$this->parser->addTagPair('C', $startTagPos, $startTagLen, $endTagPos, $endTagLen);
		$this->overwrite($startTagPos, $endTagPos + $endTagLen - $startTagPos);
	}

	/**
	* Add an image tag for given text span
	*
	* @param  integer $startTagPos Start tag position
	* @param  integer $endTagPos   End tag position
	* @param  integer $endTagLen   End tag length
	* @param  string  $linkInfo    URL optionally followed by space and a title
	* @return void
	*/
	protected function addLinkTag($startTagPos, $endTagPos, $endTagLen, $linkInfo)
	{
		$tag = $this->parser->addTagPair('URL', $startTagPos, 1, $endTagPos, $endTagLen);
		$this->setLinkAttributes($tag, $linkInfo, 'url');

		// Give the link a slightly worse priority if this is a implicit reference and a slightly
		// better priority if it's an explicit reference or an inline link or  to give it precedence
		// over possible BBCodes such as [b](https://en.wikipedia.org/wiki/B)
		$tag->setSortPriority(($endTagLen === 1) ? 1 : -1);

		// Overwrite the markup without touching the link's text
		$this->overwrite($startTagPos, 1);
		$this->overwrite($endTagPos,   $endTagLen);
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
	* Compute the amount of text to ignore at the start of a quote line
	*
	* @param  string  $str           Original quote markup
	* @param  integer $maxQuoteDepth Maximum quote depth
	* @return integer                Number of characters to ignore
	*/
	protected function computeQuoteIgnoreLen($str, $maxQuoteDepth)
	{
		$remaining = $str;
		while (--$maxQuoteDepth >= 0)
		{
			$remaining = preg_replace('/^ *> ?/', '', $remaining);
		}

		return strlen($str) - strlen($remaining);
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
		if ($this->config['decodeHtmlEntities'] && strpos($str, '&') !== false)
		{
			$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
		}
		$str = str_replace("\x1A", '', $str);

		if ($this->hasEscapedChars)
		{
			$str = strtr(
				$str,
				[
					"\x1B0" => '!', "\x1B1" => '"', "\x1B2" => "'", "\x1B3" => '(',
					"\x1B4" => ')', "\x1B5" => '*', "\x1B6" => '[', "\x1B7" => '\\',
					"\x1B8" => ']', "\x1B9" => '^', "\x1BA" => '_', "\x1BB" => '`',
					"\x1BC" => '~'
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
				'\\!' => "\x1B0", '\\"' => "\x1B1", "\\'" => "\x1B2", '\\('  => "\x1B3",
				'\\)' => "\x1B4", '\\*' => "\x1B5", '\\[' => "\x1B6", '\\\\' => "\x1B7",
				'\\]' => "\x1B8", '\\^' => "\x1B9", '\\_' => "\x1BA", '\\`'  => "\x1BB",
				'\\~' => "\x1BC"
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
	* Capture and return inline code markers
	*
	* @return array
	*/
	protected function getInlineCodeMarkers()
	{
		$pos = strpos($this->text, '`');
		if ($pos === false)
		{
			return [];
		}

		preg_match_all(
			'/(`+)(\\s*)[^\\x17`]*/',
			str_replace("\x1BB", '\\`', $this->text),
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER,
			$pos
		);
		$trimNext = 0;
		$markers  = [];
		foreach ($matches as $m)
		{
			$markers[] = [
				'pos'        => $m[0][1],
				'len'        => strlen($m[1][0]),
				'trimBefore' => $trimNext,
				'trimAfter'  => strlen($m[2][0]),
				'next'       => $m[0][1] + strlen($m[0][0])
			];
			$trimNext = strlen($m[0][0]) - strlen(rtrim($m[0][0]));
		}

		return $markers;
	}

	/**
	* Capture and return labels used in current text
	*
	* @return array Labels' text position as keys, lowercased text content as values
	*/
	protected function getLabels()
	{
		preg_match_all(
			'/\\[((?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*)\\]/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE
		);
		$labels = [];
		foreach ($matches[1] as $m)
		{
			$labels[$m[1] - 1] = strtolower($m[0]);
		}

		return $labels;
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
		return ($this->text[$matchPos] === '_' && $matchLen === 1 && $this->isSurroundedByAlnum($matchPos, $matchLen));
	}

	/**
	* Initialize this parser with given text
	*
	* @param  string $text Text to be parsed
	* @return void
	*/
	protected function init($text)
	{
		if (strpos($text, '\\') === false || !preg_match('/\\\\[!"\'()*[\\\\\\]^_`~]/', $text))
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
	* Mark the boundary of a block in the original text
	*
	* @param  integer $pos
	* @return void
	*/
	protected function markBoundary($pos)
	{
		$this->text[$pos] = "\x17";
	}

	/**
	* Match block-level markup, as well as forced line breaks and headers
	*
	* @return void
	*/
	protected function matchBlockLevelMarkup()
	{
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

		$regexp = '/^(?:(?=[-*+\\d \\t>`~#_])((?: {0,3}> ?)+)?([ \\t]+)?(\\* *\\* *\\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$|=+$)?((?:[-*+]|\\d+\\.)[ \\t]+(?=\\S))?[ \\t]*(#{1,6}[ \\t]+|```+[^`\\n]*$|~~~+[^~\\n]*$)?)?/m';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		foreach ($matches as $m)
		{
			$matchPos   = $m[0][1];
			$matchLen   = strlen($m[0][0]);
			$ignoreLen  = 0;
			$quoteDepth = 0;

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
				if (isset($codeTag) && $codeTag->hasAttribute('quoteDepth'))
				{
					$quoteDepth = min($quoteDepth, $codeTag->getAttribute('quoteDepth'));
					$ignoreLen  = $this->computeQuoteIgnoreLen($m[1][0], $quoteDepth);
				}

				// Overwrite quote markup
				$this->overwrite($matchPos, $ignoreLen);
			}

			// Close supernumerary quotes
			if ($quoteDepth < $quotesCnt && !$continuation)
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
			if (!empty($m[2][0]) && !$codeFence)
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
					$this->markBoundary($matchPos - 1);
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

				if (!$indentWidth && !$continuation && !$hasListItem)
				{
					// Start of a new context
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
					// and it's either not in continuation of a paragraph or immediately after a
					// block
					if ($hasListItem && (!$continuation || $this->text[$matchPos - 1] === "\x17"))
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

							$start = (int) $m[4][0];
							if ($start !== 1)
							{
								$listTag->setAttribute('start', $start);
							}
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
					$this->markBoundary($startTagPos);
					$this->markBoundary($lfPos);

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

					if (isset($codeTag) && $m[5][0] === $codeFence)
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
						$codeFence = substr($m[5][0], 0, strspn($m[5][0], '`~'));
						$codeTag->setAttribute('quoteDepth', $quoteDepth);

						// Ignore the next character, which should be a newline
						$this->parser->addIgnoreTag($tagPos + $tagLen, 1);

						// Add the language if present, e.g. ```php
						$lang = trim(trim($m[5][0], '`~'));
						if ($lang !== '')
						{
							$codeTag->setAttribute('lang', $lang);
						}
					}
				}
			}
			elseif (!empty($m[3][0]) && !$listsCnt && $this->text[$matchPos + $matchLen] !== "\x17")
			{
				// Horizontal rule
				$this->parser->addSelfClosingTag('HR', $matchPos + $ignoreLen, $matchLen - $ignoreLen);
				$breakParagraph = true;

				// Mark the end of the line as a boundary
				$this->markBoundary($lfPos);
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

				// Mark the end of the Setext line
				$this->markBoundary($setextLines[$lfPos]['endTagPos'] + $setextLines[$lfPos]['endTagLen']);
			}

			if ($breakParagraph)
			{
				$this->parser->addParagraphBreak($textBoundary);
				$this->markBoundary($textBoundary);
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
		if (strpos($this->text, '](', $pos) !== false)
		{
			$this->matchInlineImages();
		}
		if ($this->hasRefs)
		{
			$this->matchReferenceImages();
		}
	}

	/**
	* Match inline images markup
	*
	* @return void
	*/
	protected function matchInlineImages()
	{
		preg_match_all(
			'/!\\[(?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*\\]\\(((?:[^\\x17\\s()]|\\([^\\x17\\s()]*\\))*(?: +(?:"[^\\x17]*?"|\'[^\\x17]*?\'|\\([^\\x17\\)]*?\\)))?)\\)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);
		foreach ($matches as $m)
		{
			$linkInfo    = $m[1][0];
			$startTagPos = $m[0][1];
			$endTagLen   = 3 + strlen($linkInfo);
			$endTagPos   = $startTagPos + strlen($m[0][0]) - $endTagLen;
			$alt         = substr($m[0][0], 2, strlen($m[0][0]) - $endTagLen - 2);

			$this->addImageTag($startTagPos, $endTagPos, $endTagLen, $linkInfo, $alt);
		}
	}

	/**
	* Match reference images markup
	*
	* @return void
	*/
	protected function matchReferenceImages()
	{
		preg_match_all(
			'/!\\[((?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*)\\](?: ?\\[([^\\x17[\\]]+)\\])?/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);
		foreach ($matches as $m)
		{
			$startTagPos = $m[0][1];
			$endTagPos   = $startTagPos + 2 + strlen($m[1][0]);
			$endTagLen   = 1;
			$alt         = $m[1][0];
			$id          = $alt;

			if (isset($m[2][0], $this->refs[$m[2][0]]))
			{
				$endTagLen = strlen($m[0][0]) - strlen($alt) - 2;
				$id        = $m[2][0];
			}
			elseif (!isset($this->refs[$id]))
			{
				continue;
			}

			$this->addImageTag($startTagPos, $endTagPos, $endTagLen, $this->refs[$id], $alt);
		}
	}

	/**
	* Match inline code spans
	*
	* @return void
	*/
	protected function matchInlineCode()
	{
		$markers = $this->getInlineCodeMarkers();
		$i       = -1;
		$cnt     = count($markers);
		while (++$i < ($cnt - 1))
		{
			$pos = $markers[$i]['next'];
			$j   = $i;
			if ($this->text[$markers[$i]['pos']] !== '`')
			{
				// Adjust the left marker if its first backtick was escaped
				++$markers[$i]['pos'];
				--$markers[$i]['len'];
			}
			while (++$j < $cnt && $markers[$j]['pos'] === $pos)
			{
				if ($markers[$j]['len'] === $markers[$i]['len'])
				{
					$this->addInlineCodeTags($markers[$i], $markers[$j]);
					$i = $j;
					break;
				}
				$pos = $markers[$j]['next'];
			}
		}
	}

	/**
	* Match inline links markup
	*
	* @return void
	*/
	protected function matchInlineLinks()
	{
		preg_match_all(
			'/\\[(?:[^\\x17[\\]]|\\[[^\\x17[\\]]*\\])*\\]\\(((?:[^\\x17\\s()]|\\([^\\x17\\s()]*\\))*(?: +(?:"[^\\x17]*?"|\'[^\\x17]*?\'|\\([^\\x17\\)]*?\\)))?)\\)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE | PREG_SET_ORDER
		);
		foreach ($matches as $m)
		{
			$linkInfo    = $m[1][0];
			$startTagPos = $m[0][1];
			$endTagLen   = 3 + strlen($linkInfo);
			$endTagPos   = $startTagPos + strlen($m[0][0]) - $endTagLen;

			$this->addLinkTag($startTagPos, $endTagPos, $endTagLen, $linkInfo);
		}
	}

	/**
	* Capture link reference definitions in current text
	*
	* @return void
	*/
	protected function matchLinkReferences()
	{
		$this->hasRefs = false;
		$this->refs    = [];
		if (strpos($this->text, ']:') === false)
		{
			return;
		}

		$regexp = '/^\\x1A* {0,3}\\[([^\\x17\\]]+)\\]: *([^\\s\\x17]+ *(?:"[^\\x17]*?"|\'[^\\x17]*?\'|\\([^\\x17\\)]*?\\))?)[^\\x17\\n]*\\n?/m';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);
		foreach ($matches as $m)
		{
			$this->parser->addIgnoreTag($m[0][1], strlen($m[0][0]))->setSortPriority(-2);

			// Ignore the reference if it already exists
			$id = strtolower($m[1][0]);
			if (isset($this->refs[$id]))
			{
				continue;
			}

			$this->hasRefs   = true;
			$this->refs[$id] = $m[2][0];
		}
	}

	/**
	* Match inline and reference links
	*
	* @return void
	*/
	protected function matchLinks()
	{
		if (strpos($this->text, '](') !== false)
		{
			$this->matchInlineLinks();
		}
		if ($this->hasRefs)
		{
			$this->matchReferenceLinks();
		}
	}

	/**
	* Match reference links markup
	*
	* @return void
	*/
	protected function matchReferenceLinks()
	{
		$labels = $this->getLabels();
		foreach ($labels as $startTagPos => $id)
		{
			$labelPos  = $startTagPos + 2 + strlen($id);
			$endTagPos = $labelPos - 1;
			$endTagLen = 1;

			if ($this->text[$labelPos] === ' ')
			{
				++$labelPos;
			}
			if (isset($labels[$labelPos], $this->refs[$labels[$labelPos]]))
			{
				$id        = $labels[$labelPos];
				$endTagLen = $labelPos + 2 + strlen($id) - $endTagPos;
			}
			if (isset($this->refs[$id]))
			{
				$this->addLinkTag($startTagPos, $endTagPos, $endTagLen, $this->refs[$id]);
			}
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

	/**
	* Set a URL or IMG tag's attributes
	*
	* @param  Tag    $tag      URL or IMG tag
	* @param  string $linkInfo Link's info: an URL optionally followed by spaces and a title
	* @param  string $attrName Name of the URL attribute
	* @return void
	*/
	protected function setLinkAttributes(Tag $tag, $linkInfo, $attrName)
	{
		$url   = $linkInfo;
		$title = '';
		$pos   = strpos($linkInfo, ' ');
		if ($pos !== false)
		{
			$url   = substr($linkInfo, 0, $pos);
			$title = substr(trim(substr($linkInfo, $pos)), 1, -1);
		}

		$tag->setAttribute($attrName, $this->decode($url));
		if ($title > '')
		{
			$tag->setAttribute('title', $this->decode($title));
		}
	}
}