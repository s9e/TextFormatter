<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;
abstract class AbstractInlineMarkup extends AbstractPass
{
	protected function parseInlineMarkup(string $str, string $regexp, string $tagName): void
	{
		$pos = $this->text->indexOf($str);
		if ($pos === \false)
			return;
		\preg_match_all($regexp, $this->text, $matches, \PREG_OFFSET_CAPTURE, $pos);
		foreach ($matches[0] as [$match, $matchPos])
		{
			$matchLen = \strlen($match);
			$endPos   = $matchPos + $matchLen - 2;
			$this->parser->addTagPair($tagName, $matchPos, 2, $endPos, 2);
			$this->text->overwrite($matchPos, 2);
			$this->text->overwrite($endPos, 2);
		}
	}
}