<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;

abstract class Unparser
{
	public static function removeFormatting($xml)
	{
		$dom = new DOMDocument;
		$dom->loadXML($xml);

		foreach ($dom->getElementsByTagName('s') as $tag)
			$tag->parentNode->removeChild($tag);

		foreach ($dom->getElementsByTagName('e') as $tag)
			$tag->parentNode->removeChild($tag);

		return $dom->documentElement->textContent;
	}

	public static function unparse($xml)
	{
		return \htmlspecialchars_decode(\strip_tags($xml), \ENT_QUOTES);
	}
}