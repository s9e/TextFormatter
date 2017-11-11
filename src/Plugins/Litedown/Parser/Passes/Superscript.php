<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class Superscript extends AbstractPass
{
	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$pos = $this->text->indexOf('^');
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
		$pos = $this->text->indexOf('^(', $pos);
		if ($pos === false)
		{
			return;
		}

		preg_match_all(
			'/\\^\\([^\\x17()]++\\)/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE,
			$pos
		);
		foreach ($matches[0] as list($match, $matchPos))
		{
			$matchLen = strlen($match);

			$this->parser->addTagPair('SUP', $matchPos, 2, $matchPos + $matchLen - 1, 1);
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
		preg_match_all(
			'/\\^(?!\\()[^\\x17\\s^()]++\\^?/',
			$this->text,
			$matches,
			PREG_OFFSET_CAPTURE,
			$pos
		);
		foreach ($matches[0] as list($match, $matchPos))
		{
			$matchLen = strlen($match);
			$startPos = $matchPos;
			$endLen   = (substr($match, -1) === '^') ? 1 : 0;
			$endPos   = $matchPos + $matchLen - $endLen;

			$this->parser->addTagPair('SUP', $startPos, 1, $endPos, $endLen);
		}
	}
}