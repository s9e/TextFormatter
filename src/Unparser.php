<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;

abstract class Unparser
{
	/**
	* Strip the formatting of an intermediate representation and return plain text
	*
	* This will remove start tags and end tags but will keep the text content of everything else
	*
	* @param  string $xml Intermediate representation
	* @return string      Plain text
	*/
	public static function removeFormatting($xml)
	{
		$dom = new DOMDocument;
		$dom->loadXML($xml);

		foreach ($dom->getElementsByTagName('s') as $tag)
		{
			$tag->parentNode->removeChild($tag);
		}

		foreach ($dom->getElementsByTagName('e') as $tag)
		{
			$tag->parentNode->removeChild($tag);
		}

		return $dom->documentElement->textContent;
	}

	/**
	* Transform an intermediate representation back to its original form
	*
	* @param  string $xml Intermediate representation
	* @return string      Original text
	*/
	public static function unparse($xml)
	{
		return htmlspecialchars_decode(strip_tags($xml), ENT_QUOTES);
	}
}