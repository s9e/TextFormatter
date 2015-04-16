<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;
use DOMXPath;

abstract class Utils
{
	public static function removeFormatting($xml)
	{
		$dom = self::loadXML($xml);
		foreach ($dom->getElementsByTagName('s') as $tag)
			$tag->parentNode->removeChild($tag);
		foreach ($dom->getElementsByTagName('e') as $tag)
			$tag->parentNode->removeChild($tag);

		return $dom->documentElement->textContent;
	}

	public static function removeTag($xml, $tagName, $nestingLevel = 0)
	{
		$dom   = self::loadXML($xml);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query(\str_repeat('//' . $tagName, 1 + $nestingLevel));
		foreach ($nodes as $node)
			$node->parentNode->removeChild($node);

		return $dom->saveXML($dom->documentElement);
	}

	protected static function loadXML($xml)
	{
		$flags = (\LIBXML_VERSION >= 20700) ? \LIBXML_COMPACT | \LIBXML_PARSEHUGE : 0;

		$dom = new DOMDocument;
		$dom->loadXML($xml, $flags);

		return $dom;
	}
}