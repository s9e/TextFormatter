<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

abstract class AbstractScript extends AbstractPass
{
	/**
	* @var string $longRegexp Regexp used for the long form syntax
	*/
	protected $longRegexp;

	/**
	* @var string Regexp used for the short form syntax
	*/
	protected $shortRegexp;

	/**
	* @var string Relevant character used by this syntax
	*/
	protected $syntaxChar;
	
	/**
	* @var string Name of the tag used by this pass
	*/
	protected $tagName;

	/**
	* @param  string $tagName     Name of the tag used by this pass
	* @param  string $syntaxChar  Relevant character used by this syntax
	* @param  string $shortRegexp Regexp used for the short form syntax
	* @param  string $longRegexp  Regexp used for the long form syntax
	* @return void
	*/
	protected function parseAbstractScript($tagName, $syntaxChar, $shortRegexp, $longRegexp)
	{
		$this->tagName     = $tagName;
		$this->syntaxChar  = $syntaxChar;
		$this->shortRegexp = $shortRegexp;
		$this->longRegexp  = $longRegexp;

		$pos = $this->text->indexOf($this->syntaxChar);
		if ($pos === false)
		{
			return;
		}

		$this->parseShortForm($pos);
		$this->parseLongForm($pos);
	}

	/**
	* Parse the long form x^(x)
	*
	* This syntax is supported by RDiscount
	*
	* @param  integer $pos Position of the first relevant character
	* @return void
	*/
	protected function parseLongForm($pos)
	{
		$pos = $this->text->indexOf($this->syntaxChar . '(', $pos);
		if ($pos === false)
		{
			return;
		}

		preg_match_all($this->longRegexp, $this->text, $matches, PREG_OFFSET_CAPTURE, $pos);
		foreach ($matches[0] as list($match, $matchPos))
		{
			$matchLen = strlen($match);

			$this->parser->addTagPair($this->tagName, $matchPos, 2, $matchPos + $matchLen - 1, 1);
			$this->text->overwrite($matchPos, $matchLen);
		}
		if (!empty($matches[0]))
		{
			$this->parseLongForm($pos);
		}
	}

	/**
	* Parse the short form x^x and x^x^
	*
	* This syntax is supported by most implementations that support superscript
	*
	* @param  integer $pos Position of the first relevant character
	* @return void
	*/
	protected function parseShortForm($pos)
	{
		preg_match_all($this->shortRegexp, $this->text, $matches, PREG_OFFSET_CAPTURE, $pos);
		foreach ($matches[0] as list($match, $matchPos))
		{
			$matchLen = strlen($match);
			$startPos = $matchPos;
			$endLen   = (substr($match, -1) === $this->syntaxChar) ? 1 : 0;
			$endPos   = $matchPos + $matchLen - $endLen;

			$this->parser->addTagPair($this->tagName, $startPos, 1, $endPos, $endLen);
		}
	}
}