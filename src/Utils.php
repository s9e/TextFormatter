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
	* Remove all tags at given nesting level
	*
	* @param  string  $xml          Intermediate representation
	* @param  string  $tagName      Tag's name (case-sensitive)
	* @param  integer $nestingLevel Minimum nesting level
	* @return string                Updated intermediate representation
	*/
	public static function removeTag($xml, $tagName, $nestingLevel = 0)
	{
		$dom   = self::loadXML($xml);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query(str_repeat('//' . $tagName, 1 + $nestingLevel));
		foreach ($nodes as $node)
		{
			$node->parentNode->removeChild($node);
		}

		return $dom->saveXML($dom->documentElement);
	}

	/**
	* Replace the attributes of all tags of given name in given XML
	*
	* @param  string   $xml      Original XML
	* @param  string   $tagName  Target tag's name
	* @param  callback $callback Callback used to process attributes. Receives the old attributes
	*                            as an array, should return the new attributes as an array
	* @return string             Modified XML
	*/
	public static function replaceAttributes($xml, $tagName, callable $callback)
	{
		if (strpos($xml, '<' . $tagName) === false)
		{
			return $xml;
		}

		return preg_replace_callback(
			'((<' . preg_quote($tagName) . ')(?=[ />])[^>]*?(/?>))',
			function ($m) use ($callback)
			{
				return $m[1] . self::serializeAttributes($callback(self::parseAttributes($m[0]))) . $m[2];
			},
			$xml
		);
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

	/**
	* Parse the attributes contained in given XML
	*
	* @param  string $xml XML string, normally a start tag
	* @return array       Associative array of attribute values
	*/
	protected static function parseAttributes($xml)
	{
		$attributes = [];
		if (strpos($xml, '="') !== false)
		{
			preg_match_all('(([^ =]++)="([^"]*))S', $xml, $matches);
			foreach ($matches[1] as $i => $attrName)
			{
				$attributes[$attrName] = html_entity_decode($matches[2][$i], ENT_QUOTES, 'UTF-8');
			}
		}

		return $attributes;
	}

	/**
	* Serialize an array of attribute values
	*
	* @param  array  $attributes Associative array of attribute values
	* @return string             Attributes, sorted by name and serialized to XML
	*/
	protected static function serializeAttributes(array $attributes)
	{
		$xml = '';
		ksort($attributes);
		foreach ($attributes as $attrName => $attrValue)
		{
			$xml .= ' ' . htmlspecialchars($attrName, ENT_QUOTES) . '="' . htmlspecialchars($attrValue, ENT_QUOTES) . '"';
		}

		return $xml;
	}
}