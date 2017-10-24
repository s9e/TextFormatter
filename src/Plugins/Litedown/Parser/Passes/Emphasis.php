<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class Emphasis extends AbstractPass
{
	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$this->parseEmphasisByCharacter('*', '/\\*+/');
		$this->parseEmphasisByCharacter('_', '/_+/');
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
	* Process a list of emphasis markup strings
	*
	* @param  array[] $block List of [matchPos, matchLen] pairs
	* @return void
	*/
	protected function processEmphasisBlock(array $block)
	{
		$emPos     = null;
		$strongPos = null;
		foreach ($block as list($matchPos, $matchLen))
		{
			$canOpen      = !$this->text->isBeforeWhitespace($matchPos + $matchLen - 1);
			$canClose     = !$this->text->isAfterWhitespace($matchPos);
			$closeLen     = ($canClose) ? min($matchLen, 3) : 0;
			$closeEm      = ($closeLen & 1) && isset($emPos);
			$closeStrong  = ($closeLen & 2) && isset($strongPos);
			$emEndPos     = $matchPos;
			$strongEndPos = $matchPos;
			$remaining    = $matchLen;

			if (isset($emPos) && $emPos === $strongPos)
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

			if ($closeEm)
			{
				--$remaining;
				$this->parser->addTagPair('EM', $emPos, 1, $emEndPos, 1);
				$emPos = null;
			}
			if ($closeStrong)
			{
				$remaining -= 2;
				$this->parser->addTagPair('STRONG', $strongPos, 2, $strongEndPos, 2);
				$strongPos = null;
			}

			if ($canOpen)
			{
				$remaining = min($remaining, 3);
				if ($remaining & 1)
				{
					$emPos     = $matchPos + $matchLen - $remaining;
				}
				if ($remaining & 2)
				{
					$strongPos = $matchPos + $matchLen - $remaining;
				}
			}
		}
	}
}