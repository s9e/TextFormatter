<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
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
	* @link http://www.w3.org/TR/xslt#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value
	* @return array             Array of tokens
	*/
	public static function parse($attrValue)
	{
		$tokens  = [];
		$attrLen = strlen($attrValue);

		$pos = 0;
		while ($pos < $attrLen)
		{
			// Look for opening brackets
			if ($attrValue[$pos] === '{')
			{
				// Two brackets = one literal bracket
				if (substr($attrValue, $pos, 2) === '{{')
				{
					$tokens[] = ['literal', '{'];
					$pos += 2;

					continue;
				}

				// Move the cursor past the left bracket
				++$pos;

				// We're inside an inline XPath expression. We need to parse it in order to find
				// where it ends
				$expr = '';
				while ($pos < $attrLen)
				{
					// Capture everything up to the next "interesting" char: ', " or }
					$spn = strcspn($attrValue, '\'"}', $pos);
					if ($spn)
					{
						$expr .= substr($attrValue, $pos, $spn);
						$pos += $spn;
					}

					if ($pos >= $attrLen)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the character then move the cursor
					$c = $attrValue[$pos];
					++$pos;

					if ($c === '}')
					{
						// Done with this expression
						break;
					}

					// Look for the matching quote
					$quotePos = strpos($attrValue, $c, $pos);
					if ($quotePos === false)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the content of that string then move the cursor past it
					$expr .= $c . substr($attrValue, $pos, $quotePos + 1 - $pos);
					$pos = 1 + $quotePos;
				}

				$tokens[] = ['expression', $expr];
			}

			$spn = strcspn($attrValue, '{', $pos);
			if ($spn)
			{
				// Capture this chunk of attribute value
				$str = substr($attrValue, $pos, $spn);

				// Unescape right brackets
				$str = str_replace('}}', '}', $str);

				// Add the value and move the cursor
				$tokens[] = ['literal', $str];
				$pos += $spn;
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
			if ($type === 'literal')
			{
				$xsl .= htmlspecialchars($content, ENT_NOQUOTES, 'UTF-8');
			}
			else
			{
				$xsl .= '<xsl:value-of select="' . htmlspecialchars($content, ENT_COMPAT, 'UTF-8') . '"/>';
			}
		}

		return $xsl;
	}
}