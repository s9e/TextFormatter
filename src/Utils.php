<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
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
		if (strpos($xml, $tagName) !== false)
		{
			$regexp = '((?<=<)' . preg_quote($tagName) . '(?= )[^>]*? ' . preg_quote($attrName) . '="\\K[^"]*+)';
			preg_match_all($regexp, $xml, $matches);
			foreach ($matches[0] as $value)
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
		$dom   = self::loadXML($xml);
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//e | //s') as $node)
		{
			$node->parentNode->removeChild($node);
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
		if (strpos($xml, $tagName) === false)
		{
			return $xml;
		}

		$dom   = self::loadXML($xml);
		$xpath = new DOMXPath($dom);
		$query = '//' . $tagName . '[count(ancestor::' . $tagName . ') >= ' . $nestingLevel . ']';
		$nodes = $xpath->query($query);
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
	* @param  callable $callback Callback used to process attributes. Receives the old attributes
	*                            as an array, should return the new attributes as an array
	* @return string             Modified XML
	*/
	public static function replaceAttributes($xml, $tagName, callable $callback)
	{
		if (strpos($xml, $tagName) === false)
		{
			return $xml;
		}

		return preg_replace_callback(
			'((?<=<)' . preg_quote($tagName) . '(?=[ />])\\K[^>]*+)',
			function ($m) use ($callback)
			{
				$str = self::serializeAttributes($callback(self::parseAttributes($m[0])));
				if (substr($m[0], -1) === '/')
				{
					$str .= '/';
				}

				return $str;
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
		$cp   = (ord($utf8[0]) << 18) + (ord($utf8[1]) << 12) + (ord($utf8[2]) << 6) + ord($utf8[3]) - 0x3C82080;

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
			preg_match_all('(([^ =]++)="([^"]*))', $xml, $matches);
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
			$xml .= ' ' . htmlspecialchars($attrName, ENT_QUOTES) . '="' . htmlspecialchars($attrValue, ENT_COMPAT) . '"';
		}

		// Normalize control characters to what the parser would normally produce
		$xml = preg_replace('/\\r\\n?/', "\n", $xml);
		$xml = preg_replace('/[\\x00-\\x08\\x0B\\x0C\\x0E-\\x1F]+/S', '', $xml);
		$xml = str_replace("\n", '&#10;', $xml);

		return self::encodeUnicodeSupplementaryCharacters($xml);
	}
}