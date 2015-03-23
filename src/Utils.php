<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;
use DOMXPath;

abstract class Utils
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
		$dom = self::loadXML($xml);
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
	* Create a return a new DOMDocument loaded with given XML
	*
	* @param  string      $xml Source XML
	* @return DOMDocument
	*/
	protected static function loadXML($xml)
	{
		// Activate small nodes allocation and relax LibXML's hardcoded limits if applicable
		$flags = (LIBXML_VERSION >= 20700) ? LIBXML_COMPACT | LIBXML_PARSEHUGE : 0;

		$dom = new DOMDocument;
		$dom->loadXML($xml, $flags);

		return $dom;
	}
}