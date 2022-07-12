<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class Emphasis extends AbstractPass
{
	/**
	* @var bool Whether current EM span is being closed by current emphasis mark
	*/
	protected $closeEm;

	/**
	* @var bool Whether current EM span is being closed by current emphasis mark
	*/
	protected $closeStrong;

	/**
	* @var integer Starting position of the current EM span in the text
	*/
	protected $emPos;

	/**
	* @var integer Ending position of the current EM span in the text
	*/
	protected $emEndPos;

	/**
	* @var integer Number of emphasis characters unused in current span
	*/
	protected $remaining;

	/**
	* @var integer Starting position of the current STRONG span in the text
	*/
	protected $strongPos;

	/**
	* @var integer Ending position of the current STRONG span in the text
	*/
	protected $strongEndPos;

	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$this->parseEmphasisByCharacter('*', '/\\*+/');
		$this->parseEmphasisByCharacter('_', '/_+/');
	}

	/**
	* Adjust the ending position of current EM and STRONG spans
	*
	* @return void
	*/
	protected function adjustEndingPositions()
	{
		if ($this->closeEm && $this->closeStrong)
		{
			if ($this->emPos < $this->strongPos)
			{
				$this->emEndPos += 2;
			}
			else
			{
				++$this->strongEndPos;
			}
		}
	}

	/**
	* Adjust the starting position of current EM and STRONG spans
	*
	* If both EM and STRONG are set to start at the same position, we adjust their position
	* to match the order they are closed. If they start and end at the same position, STRONG
	* starts before EM to match Markdown's behaviour
	*
	* @return void
	*/
	protected function adjustStartingPositions()
	{
		if ($this->emPos >= 0 && $this->emPos === $this->strongPos)
		{
			if ($this->closeEm)
			{
				$this->emPos += 2;
			}
			else
			{
				++$this->strongPos;
			}
		}
	}

	/**
	* End current valid EM and STRONG spans
	*
	* @return void
	*/
	protected function closeSpans()
	{
		if ($this->closeEm)
		{
			--$this->remaining;
			$this->parser->addTagPair('EM', $this->emPos, 1, $this->emEndPos, 1);
			$this->emPos = -1;
		}
		if ($this->closeStrong)
		{
			$this->remaining -= 2;
			$this->parser->addTagPair('STRONG', $this->strongPos, 2, $this->strongEndPos, 2);
			$this->strongPos = -1;
		}
	}

	/**
	* Parse emphasis and strong applied using given character
	*
	* @param  string $character Markup character, either * or _
	* @param  string $regexp    Regexp used to match the series of emphasis character
	* @return void
	*/
	protected function parseEmphasisByCharacter($character, $regexp)
	{
		$pos = $this->text->indexOf($character);
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
		$breakPos = $this->text->indexOf("\x17", $pos);

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
				$breakPos = $this->text->indexOf("\x17", $matchPos);
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
		return ($this->text->charAt($matchPos) === '_' && $matchLen === 1 && $this->text->isSurroundedByAlnum($matchPos, $matchLen));
	}

	/**
	* Open EM and STRONG spans whose content starts at given position
	*
	* @param  integer $pos
	* @return void
	*/
	protected function openSpans($pos)
	{
		if ($this->remaining & 1)
		{
			$this->emPos     = $pos - $this->remaining;
		}
		if ($this->remaining & 2)
		{
			$this->strongPos = $pos - $this->remaining;
		}
	}

	/**
	* Process a list of emphasis markup strings
	*
	* @param  array[] $block List of [matchPos, matchLen] pairs
	* @return void
	*/
	protected function processEmphasisBlock(array $block)
	{
		$this->emPos     = -1;
		$this->strongPos = -1;
		foreach ($block as list($matchPos, $matchLen))
		{
			$this->processEmphasisMatch($matchPos, $matchLen);
		}
	}

	/**
	* Process an emphasis mark
	*
	* @param  integer $matchPos
	* @param  integer $matchLen
	* @return void
	*/
	protected function processEmphasisMatch($matchPos, $matchLen)
	{
		$canOpen  = !$this->text->isBeforeWhitespace($matchPos + $matchLen - 1);
		$canClose = !$this->text->isAfterWhitespace($matchPos);
		$closeLen = ($canClose) ? min($matchLen, 3) : 0;

		$this->closeEm      = ($closeLen & 1) && $this->emPos     >= 0;
		$this->closeStrong  = ($closeLen & 2) && $this->strongPos >= 0;
		$this->emEndPos     = $matchPos;
		$this->strongEndPos = $matchPos;
		$this->remaining    = $matchLen;

		$this->adjustStartingPositions();
		$this->adjustEndingPositions();
		$this->closeSpans();

		// Adjust the length of unused markup remaining in current match
		$this->remaining = ($canOpen) ? min($this->remaining, 3) : 0;
		$this->openSpans($matchPos + $matchLen);
	}
}