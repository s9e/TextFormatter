<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter;

use DOMDocument;
use DOMXPath;

abstract class Utils
{
	/**
	* Return every value used in given attribute from given tag
	*
	* Will include duplicate values
	*
	* @param  string   $xml      Parsed text
	* @param  string   $tagName  Target tag's name
	* @param  string   $attrName Target attribute's name
	* @return string[]           Attribute values
	*/
	public static function getAttributeValues($xml, $tagName, $attrName)
	{
		$values = [];
		if (strpos($xml, '<' . $tagName) !== false)
		{
			$regexp = '(<' . preg_quote($tagName) . '(?= )[^>]*? ' . preg_quote($attrName) . '="([^"]*+))';
			preg_match_all($regexp, $xml, $matches);
			foreach ($matches[1] as $value)
			{
				$values[] = html_entity_decode($value, ENT_QUOTES, 'UTF-8');
			}
		}

		return $values;
	}

	/**
	* Replace Unicode characters outside the BMP with XML entities
	*
	* @param  string $str Original string
	* @return string      String with SMP characters encoded
	*/
	public static function encodeUnicodeSupplementaryCharacters($str)
	{
		return preg_replace_callback(
			'([\\xF0-\\xF4]...)S',
			__CLASS__ . '::encodeUnicodeSupplementaryCharactersCallback',
			$str
		);
	}

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
		// Traverse backwards because the indices change as we remove items
		$s = $dom->getElementsByTagName('s');
		for ($i = $s->length - 1; $i > -1; $i--)
		{
			$tag = $s->item($i);
			$tag->parentNode->removeChild($tag);
		}
		$e = $dom->getElementsByTagName('e');
		for ($i = $e->length - 1; $i > -1; $i--)
		{
			$tag = $e->item($i);
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
		if (strpos($xml, '<' . $tagName) === false)
		{
			return $xml;
		}

		$dom   = self::loadXML($xml);
		$xpath = new DOMXPath($dom);
		$nodes = $xpath->query(str_repeat('//' . $tagName, 1 + $nestingLevel));
		foreach ($nodes as $node)
		{
			$node->parentNode->removeChild($node);
		}

		return self::saveXML($dom);
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
	* Encode given Unicode character into an XML entity
	*
	* @param  string[] $m Array of captures
	* @return string      Encoded character
	*/
	protected static function encodeUnicodeSupplementaryCharactersCallback(array $m)
	{
		$utf8 = $m[0];
		$cp   = ((ord($utf8[0]) & 7)  << 18)
		      | ((ord($utf8[1]) & 63) << 12)
		      | ((ord($utf8[2]) & 63) << 6)
		      | (ord($utf8[3]) & 63);

		return '&#' . $cp . ';';
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
	* Serialize given DOMDocument
	*
	* @param  DOMDocument $dom
	* @return string
	*/
	protected static function saveXML(DOMDocument $dom)
	{
		return self::encodeUnicodeSupplementaryCharacters($dom->saveXML($dom->documentElement));
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
			$xml .= ' ' . htmlspecialchars($attrName, ENT_QUOTES) . '="' . self::encodeUnicodeSupplementaryCharacters(htmlspecialchars($attrValue, ENT_COMPAT)) . '"';
		}

		return $xml;
	}
}