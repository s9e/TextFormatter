<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;

abstract class Unparser
{
	/**
	* Transform an intermediate representation back to its original form
	*
	* @param  string $xml Intermediate representation
	* @return string      Original text
	*/
	public static function unparse($xml)
	{
		return html_entity_decode(strip_tags($xml), ENT_QUOTES, 'UTF-8');
	}

	/**
	* Transform an intermediate representation into plain text
	*
	* This will remove start tags and end tags but will keep everything else
	*
	* @param  string $xml Intermediate representation
	* @return string      Plain text
	*/
	public static function asPlainText($xml)
	{
		$dom = new DOMDocument;
		$dom->loadXML($xml);

		foreach ($dom->getElementsByTagName('st') as $tag)
		{
			$tag->parentNode->removeChild($tag);
		}

		foreach ($dom->getElementsByTagName('et') as $tag)
		{
			$tag->parentNode->removeChild($tag);
		}

		return $dom->documentElement->textContent;
	}
}