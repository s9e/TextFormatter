<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use RuntimeException;

abstract class AVTHelper
{
	/**
	* Parse an attribute value template
	*
	* @link https://www.w3.org/TR/1999/REC-xslt-19991116#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value
	* @return array             Array of tokens
	*/
	public static function parse($attrValue)
	{
		preg_match_all('((*MARK:literal)(?:[^{]|\\{\\{)++|(*MARK:expression)\\{(?:[^}"\']|"[^"]*+"|\'[^\']*+\')++\\}|(*MARK:junk).++)s', $attrValue, $matches);

		$tokens  = [];
		foreach ($matches[0] as $i => $str)
		{
			if ($matches['MARK'][$i] === 'expression')
			{
				$tokens[] = ['expression', substr($str, 1, -1)];
			}
			else
			{
				$tokens[] = ['literal', strtr($str, ['{{' => '{', '}}' => '}'])];
			}
		}

		return $tokens;
	}

	/**
	* Replace the value of an attribute via the provided callback
	*
	* The callback will receive an array containing the type and value of each token in the AVT.
	* Its return value should use the same format
	*
	* @param  DOMAttr  $attribute
	* @param  callable $callback
	* @return void
	*/
	public static function replace(DOMAttr $attribute, callable $callback)
	{
		$tokens = self::parse($attribute->value);
		foreach ($tokens as $k => $token)
		{
			$tokens[$k] = $callback($token);
		}

		$attribute->value = htmlspecialchars(self::serialize($tokens), ENT_NOQUOTES, 'UTF-8');
	}

	/**
	* Serialize an array of AVT tokens back into an attribute value
	*
	* @param  array  $tokens
	* @return string
	*/
	public static function serialize(array $tokens)
	{
		$attrValue = '';
		foreach ($tokens as $token)
		{
			if ($token[0] === 'literal')
			{
				$attrValue .= preg_replace('([{}])', '$0$0', $token[1]);
			}
			elseif ($token[0] === 'expression')
			{
				$attrValue .= '{' . $token[1] . '}';
			}
			else
			{
				throw new RuntimeException('Unknown token type');
			}
		}

		return $attrValue;
	}

	/**
	* Transform given attribute value template into an XSL fragment
	*
	* @param  string $attrValue
	* @return string
	*/
	public static function toXSL($attrValue)
	{
		$xsl = '';
		foreach (self::parse($attrValue) as list($type, $content))
		{
			if ($type === 'expression')
			{
				$xsl .= '<xsl:value-of select="' . htmlspecialchars($content, ENT_COMPAT, 'UTF-8') . '"/>';
			}
			elseif (trim($content) !== $content)
			{
				$xsl .= '<xsl:text>' . htmlspecialchars($content, ENT_NOQUOTES, 'UTF-8') . '</xsl:text>';
			}
			else
			{
				$xsl .= htmlspecialchars($content, ENT_NOQUOTES, 'UTF-8');
			}
		}

		return $xsl;
	}
}