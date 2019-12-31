<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

abstract class AbstractInlineMarkup extends AbstractPass
{
	/**
	* Parse given inline markup in text
	*
	* The markup must start and end with exactly 2 characters
	*
	* @param  string $str     First markup string
	* @param  string $regexp  Regexp used to match the markup's span
	* @param  string $tagName Name of the tag produced by this markup
	* @return void
	*/
	protected function parseInlineMarkup(string $str, string $regexp, string $tagName): void
	{
		$pos = $this->text->indexOf($str);
		if ($pos === false)
		{
			return;
		}

		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE, $pos);
		foreach ($matches[0] as [$match, $matchPos])
		{
			$matchLen = strlen($match);
			$endPos   = $matchPos + $matchLen - 2;

			$this->parser->addTagPair($tagName, $matchPos, 2, $endPos, 2);
			$this->text->overwrite($matchPos, 2);
			$this->text->overwrite($endPos, 2);
		}
	}
}