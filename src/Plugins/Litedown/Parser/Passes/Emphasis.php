<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;
class Emphasis extends AbstractPass
{
	public function parse()
	{
		$this->parseEmphasisByCharacter('*', '/\\*+/');
		$this->parseEmphasisByCharacter('_', '/_+/');
	}
	protected function parseEmphasisByCharacter($character, $regexp)
	{
		$pos = $this->text->indexOf($character);
		if ($pos === \false)
			return;
		foreach ($this->getEmphasisByBlock($regexp, $pos) as $block)
			$this->processEmphasisBlock($block);
	}
	protected function getEmphasisByBlock($regexp, $pos)
	{
		$block    = array();
		$blocks   = array();
		$breakPos = $this->text->indexOf("\x17", $pos);
		\preg_match_all($regexp, $this->text, $matches, \PREG_OFFSET_CAPTURE, $pos);
		foreach ($matches[0] as $m)
		{
			$matchPos = $m[1];
			$matchLen = \strlen($m[0]);
			if ($matchPos > $breakPos)
			{
				$blocks[] = $block;
				$block    = array();
				$breakPos = $this->text->indexOf("\x17", $matchPos);
			}
			if (!$this->ignoreEmphasis($matchPos, $matchLen))
				$block[] = array($matchPos, $matchLen);
		}
		$blocks[] = $block;
		return $blocks;
	}
	protected function ignoreEmphasis($matchPos, $matchLen)
	{
		return ($this->text->charAt($matchPos) === '_' && $matchLen === 1 && $this->text->isSurroundedByAlnum($matchPos, $matchLen));
	}
	protected function processEmphasisBlock(array $block)
	{
		$emPos     = \null;
		$strongPos = \null;
		foreach ($block as $_aab3a45e)
		{
			list($matchPos, $matchLen) = $_aab3a45e;
			$canOpen      = !$this->text->isBeforeWhitespace($matchPos + $matchLen - 1);
			$canClose     = !$this->text->isAfterWhitespace($matchPos);
			$closeLen     = ($canClose) ? \min($matchLen, 3) : 0;
			$closeEm      = ($closeLen & 1) && isset($emPos);
			$closeStrong  = ($closeLen & 2) && isset($strongPos);
			$emEndPos     = $matchPos;
			$strongEndPos = $matchPos;
			$remaining    = $matchLen;
			if (isset($emPos) && $emPos === $strongPos)
				if ($closeEm)
					$emPos += 2;
				else
					++$strongPos;
			if ($closeEm && $closeStrong)
				if ($emPos < $strongPos)
					$emEndPos += 2;
				else
					++$strongEndPos;
			if ($closeEm)
			{
				--$remaining;
				$this->parser->addTagPair('EM', $emPos, 1, $emEndPos, 1);
				$emPos = \null;
			}
			if ($closeStrong)
			{
				$remaining -= 2;
				$this->parser->addTagPair('STRONG', $strongPos, 2, $strongEndPos, 2);
				$strongPos = \null;
			}
			if ($canOpen)
			{
				$remaining = \min($remaining, 3);
				if ($remaining & 1)
					$emPos     = $matchPos + $matchLen - $remaining;
				if ($remaining & 2)
					$strongPos = $matchPos + $matchLen - $remaining;
			}
		}
	}
}