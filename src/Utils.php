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

	public static function replaceAttributes($xml, $tagName, callable $callback)
	{
		if (\strpos($xml, '<' . $tagName) === \false)
			return $xml;

		return \preg_replace_callback(
			'((<' . \preg_quote($tagName) . ')(?=[ />])[^>]*?(/?>))',
			function ($m) use ($callback)
			{
				return $m[1] . self::serializeAttributes($callback(self::parseAttributes($m[0]))) . $m[2];
			},
			$xml
		);
	}

	protected static function loadXML($xml)
	{
		$flags = (\LIBXML_VERSION >= 20700) ? \LIBXML_COMPACT | \LIBXML_PARSEHUGE : 0;

		$dom = new DOMDocument;
		$dom->loadXML($xml, $flags);

		return $dom;
	}

	protected static function parseAttributes($xml)
	{
		$attributes = [];
		if (\strpos($xml, '="') !== \false)
		{
			\preg_match_all('(([^ =]++)="([^"]*))S', $xml, $matches);
			foreach ($matches[1] as $i => $attrName)
				$attributes[$attrName] = \html_entity_decode($matches[2][$i], \ENT_QUOTES, 'UTF-8');
		}

		return $attributes;
	}

	protected static function serializeAttributes(array $attributes)
	{
		$xml = '';
		\ksort($attributes);
		foreach ($attributes as $attrName => $attrValue)
			$xml .= ' ' . \htmlspecialchars($attrName, \ENT_QUOTES) . '="' . \htmlspecialchars($attrValue, \ENT_QUOTES) . '"';

		return $xml;
	}
}