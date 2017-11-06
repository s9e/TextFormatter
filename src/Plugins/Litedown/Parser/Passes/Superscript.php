<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;
class Superscript extends AbstractPass
{
	public function parse()
	{
		$pos = $this->text->indexOf('^');
		if ($pos === \false)
			return;
		\preg_match_all(
			'/\\^[^\\x17\\s]++/',
			$this->text,
			$matches,
			\PREG_OFFSET_CAPTURE,
			$pos
		);
		foreach ($matches[0] as $_4b034d25)
		{
			list($match, $matchPos) = $_4b034d25;
			$matchLen = \strlen($match);
			$startPos = $matchPos;
			$endPos   = $matchPos + $matchLen;
			$parts = \explode('^', $match);
			unset($parts[0]);
			foreach ($parts as $part)
			{
				$this->parser->addTagPair('SUP', $startPos, 1, $endPos, 0);
				$startPos += 1 + \strlen($part);
			}
		}
	}
}