<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser\Passes;

class InlineCode extends AbstractPass
{
	/**
	* {@inheritdoc}
	*/
	public function parse()
	{
		$markers = $this->getInlineCodeMarkers();
		$i       = -1;
		$cnt     = count($markers);
		while (++$i < ($cnt - 1))
		{
			$pos = $markers[$i]['next'];
			$j   = $i;
			if ($this->text->charAt($markers[$i]['pos']) !== '`')
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
	* Add the tag pair for an inline code span
	*
	* @param  array $left  Left marker
	* @param  array $right Right marker
	* @return void
	*/
	protected function addInlineCodeTags($left, $right)
	{
		$startPos = $left['pos'];
		$startLen = $left['len'] + $left['trimAfter'];
		$endPos   = $right['pos'] - $right['trimBefore'];
		$endLen   = $right['len'] + $right['trimBefore'];
		$this->parser->addTagPair('C', $startPos, $startLen, $endPos, $endLen);
		$this->text->overwrite($startPos, $endPos + $endLen - $startPos);
	}

	/**
	* Capture and return inline code markers
	*
	* @return array
	*/
	protected function getInlineCodeMarkers()
	{
		$pos = $this->text->indexOf('`');
		if ($pos === false)
		{
			return [];
		}

		preg_match_all(
			'/(`+)(\\s*)[^\\x17`]*/',
			str_replace("\x1BD", '\\`', $this->text),
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
}