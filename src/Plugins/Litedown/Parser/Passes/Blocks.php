<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

use s9e\TextFormatter\Parser as Rules;

class Blocks extends AbstractPass
{
	/**
	* @var array
	*/
	protected $setextLines = [];

	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$this->matchSetextLines();

		$blocks       = [];
		$blocksCnt    = 0;
		$codeFence    = null;
		$codeIndent   = 4;
		$codeTag      = null;
		$lineIsEmpty  = true;
		$lists        = [];
		$listsCnt     = 0;
		$newContext   = false;
		$textBoundary = 0;

		$regexp = '/^(?:(?=[-*+\\d \\t>`~#_])((?: {0,3}>(?:(?!!)|!(?![^\\n>]*?!<)) ?)+)?([ \\t]+)?(\\* *\\* *\\*[* ]*$|- *- *-[- ]*$|_ *_ *_[_ ]*$|=+$)?((?:[-*+]|\\d+\\.)[ \\t]+(?=\\S))?[ \\t]*(#{1,6}[ \\t]+|```+[^`\\n]*$|~~~+[^~\\n]*$)?)?/m';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE | PREG_SET_ORDER);

		foreach ($matches as $m)
		{
			$blockDepth = 0;
			$blockMarks = [];
			$ignoreLen  = 0;
			$matchLen   = strlen($m[0][0]);
			$matchPos   = $m[0][1];

			// If the last line was empty then this is not a continuation, and vice-versa
			$continuation = !$lineIsEmpty;

			// Capture the position of the end of the line and determine whether the line is empty
			$lfPos       = $this->text->indexOf("\n", $matchPos);
			$lineIsEmpty = ($lfPos === $matchPos + $matchLen && empty($m[3][0]) && empty($m[4][0]) && empty($m[5][0]));

			// If the line is empty and it's the first empty line then we break current paragraph.
			$breakParagraph = ($lineIsEmpty && $continuation);

			// Count block marks
			if (!empty($m[1][0]))
			{
				$blockMarks = $this->getBlockMarks($m[1][0]);
				$blockDepth = count($blockMarks);
				$ignoreLen  = strlen($m[1][0]);
				if (isset($codeTag) && $codeTag->hasAttribute('blockDepth'))
				{
					$blockDepth = min($blockDepth, $codeTag->getAttribute('blockDepth'));
					$ignoreLen  = $this->computeBlockIgnoreLen($m[1][0], $blockDepth);
				}

				// Overwrite block markup
				$this->text->overwrite($matchPos, $ignoreLen);
			}

			// Close supernumerary blocks
			if ($blockDepth < $blocksCnt && !$continuation)
			{
				$newContext = true;
				do
				{
					$startTag = array_pop($blocks);
					$this->parser->addEndTag($startTag->getName(), $textBoundary, 0)
					             ->pairWith($startTag);
				}
				while ($blockDepth < --$blocksCnt);
			}

			// Open new blocks
			if ($blockDepth > $blocksCnt && !$lineIsEmpty)
			{
				$newContext = true;
				do
				{
					$tagName  = ($blockMarks[$blocksCnt] === '>!') ? 'SPOILER' : 'QUOTE';
					$blocks[] = $this->parser->addStartTag($tagName, $matchPos, 0, -999);
				}
				while ($blockDepth > ++$blocksCnt);
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
					if ($textBoundary > $codeTag->getPos())
					{
						// Overwrite the whole block
						$this->text->overwrite($codeTag->getPos(), $textBoundary - $codeTag->getPos());
						$codeTag->pairWith($this->parser->addEndTag('CODE', $textBoundary, 0, -1));
					}
					else
					{
						// The code block is empty
						$codeTag->invalidate();
					}

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
					$this->text->markBoundary($matchPos - 1);
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
						$codeTag = $this->parser->addStartTag('CODE', $matchPos + $ignoreLen, 0, -999);
					}

					// Clear the captures to prevent any further processing
					$m = [];
				}
			}
			elseif (!isset($codeTag))
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
					$listIndex = ($hasListItem) ? 0 : -1;
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
					$this->text->overwrite($tagPos, $tagLen);

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
					$startLen = strlen($m[5][0]);
					$startPos = $matchPos + $matchLen - $startLen;
					$endLen   = $this->getAtxHeaderEndTagLen($matchPos + $matchLen, $lfPos);
					$endPos   = $lfPos - $endLen;

					$this->parser->addTagPair('H' . strspn($m[5][0], '#', 0, 6), $startPos, $startLen, $endPos, $endLen);

					// Mark the start and the end of the header as boundaries
					$this->text->markBoundary($startPos);
					$this->text->markBoundary($lfPos);

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
						$codeTag->pairWith($this->parser->addEndTag('CODE', $tagPos, $tagLen, -1));
						$this->parser->addIgnoreTag($textBoundary, $tagPos - $textBoundary);

						// Overwrite the whole block
						$this->text->overwrite($codeTag->getPos(), $tagPos + $tagLen - $codeTag->getPos());
						$codeTag = null;
						$codeFence = null;
					}
					elseif (!isset($codeTag))
					{
						// Create code block
						$codeTag   = $this->parser->addStartTag('CODE', $tagPos, $tagLen);
						$codeFence = substr($m[5][0], 0, strspn($m[5][0], '`~'));
						$codeTag->setAttribute('blockDepth', $blockDepth);

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
			elseif (!empty($m[3][0]) && !$listsCnt && $this->text->charAt($matchPos + $matchLen) !== "\x17")
			{
				// Horizontal rule
				$this->parser->addSelfClosingTag('HR', $matchPos + $ignoreLen, $matchLen - $ignoreLen);
				$breakParagraph = true;

				// Mark the end of the line as a boundary
				$this->text->markBoundary($lfPos);
			}
			elseif (isset($this->setextLines[$lfPos]) && $this->setextLines[$lfPos]['blockDepth'] === $blockDepth && !$lineIsEmpty && !$listsCnt && !isset($codeTag))
			{
				// Setext-style header
				$this->parser->addTagPair(
					$this->setextLines[$lfPos]['tagName'],
					$matchPos + $ignoreLen,
					0,
					$this->setextLines[$lfPos]['endPos'],
					$this->setextLines[$lfPos]['endLen']
				);

				// Mark the end of the Setext line
				$this->text->markBoundary($this->setextLines[$lfPos]['endPos'] + $this->setextLines[$lfPos]['endLen']);
			}

			if ($breakParagraph)
			{
				$this->parser->addParagraphBreak($textBoundary);
				$this->text->markBoundary($textBoundary);
			}

			if (!$lineIsEmpty)
			{
				$textBoundary = $lfPos;
			}

			if ($ignoreLen)
			{
				$this->parser->addIgnoreTag($matchPos, $ignoreLen, 1000);
			}
		}
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
	* Compute the amount of text to ignore at the start of a block line
	*
	* @param  string  $str           Original block markup
	* @param  integer $maxBlockDepth Maximum block depth
	* @return integer                Number of characters to ignore
	*/
	protected function computeBlockIgnoreLen($str, $maxBlockDepth)
	{
		$remaining = $str;
		while (--$maxBlockDepth >= 0)
		{
			$remaining = preg_replace('/^ *>!? ?/', '', $remaining);
		}

		return strlen($str) - strlen($remaining);
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
	* Capture and return block marks from given string
	*
	* @param  string   $str Block markup, composed of ">", "!" and whitespace
	* @return string[]
	*/
	protected function getBlockMarks($str)
	{
		preg_match_all('(>!?)', $str, $m);

		return $m[0];
	}

	/**
	* Capture and store lines that contain a Setext-tyle header
	*
	* @return void
	*/
	protected function matchSetextLines()
	{
		if ($this->text->indexOf('-') === false && $this->text->indexOf('=') === false)
		{
			return;
		}

		// Capture the any series of - or = alone on a line, optionally preceded with the
		// angle brackets notation used in block markup
		$regexp = '/^(?=[-=>])(?:>!? ?)*(?=[-=])(?:-+|=+) *$/m';
		if (!preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE))
		{
			return;
		}

		foreach ($matches[0] as list($match, $matchPos))
		{
			// Compute the position of the end tag. We start on the LF character before the
			// match and keep rewinding until we find a non-space character
			$endPos = $matchPos - 1;
			while ($endPos > 0 && $this->text->charAt($endPos - 1) === ' ')
			{
				--$endPos;
			}

			// Store at the offset of the LF character
			$this->setextLines[$matchPos - 1] = [
				'endLen'     => $matchPos + strlen($match) - $endPos,
				'endPos'     => $endPos,
				'blockDepth' => substr_count($match, '>'),
				'tagName'    => ($match[0] === '=') ? 'H1' : 'H2'
			];
		}
	}
}