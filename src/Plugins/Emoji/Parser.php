<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		foreach ($matches as $m)
		{
			$this->parser->addSelfClosingTag($this->config['tagName'], $m[0][1], strlen($m[0][0]))
			             ->setAttribute($this->config['attrName'], $this->getSequence($m[0][0]));
		}
	}

	/**
	* 
	*
	* @param  string $str
	* @return string
	*/
	protected function getSequence($str)
	{
		$str = str_replace("\xEF\xB8\x8F", '', $str);
		$seq = [];
		$i   = 0;
		do
		{
			$cp = ord($str[$i]);
			if ($cp >= 0b11110000)
			{
				$cp = (($cp & 7) << 18) | ((ord($str[++$i]) & 63) << 12) | ((ord($str[++$i]) & 63) << 6) | (ord($str[++$i]) & 63);
			}
			elseif ($cp >= 0b11100000)
			{
				$cp = (($cp & 15) << 12) | ((ord($str[++$i]) & 63) << 6) | (ord($str[++$i]) & 63);
			}
			elseif ($cp >= 0b11000000)
			{
				$cp = (($cp & 15) << 6) | (ord($str[++$i]) & 63);
			}
			$seq[] = dechex($cp);
		}
		while (++$i < strlen($str));

		return implode('-', $seq);
	}
}