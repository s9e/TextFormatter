<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;
use s9e\TextFormatter\Utils\XPath;

abstract class XPathHelper
{
	/**
	* Return the list of variables used in a given XPath expression
	*
	* @param  string $expr XPath expression
	* @return array        Alphabetically sorted list of unique variable names
	*/
	public static function getVariables($expr)
	{
		// First, remove strings' contents to prevent false-positives
		$expr = preg_replace('/(["\']).*?\\1/s', '$1$1', $expr);

		// Capture all the variable names
		preg_match_all('/\\$(\\w+)/', $expr, $matches);

		// Dedupe and sort names
		$varNames = array_unique($matches[1]);
		sort($varNames);

		return $varNames;
	}

	/**
	* Determine whether given XPath expression definitely evaluates to a number
	*
	* @param  string $expr XPath expression
	* @return bool         Whether given XPath expression definitely evaluates to a number
	*/
	public static function isExpressionNumeric($expr)
	{
		// Trim the expression and remove parentheses that are not part of a function call. PCRE
		// does not support lookbehind assertions of variable length so we have to flip the string.
		// We exclude the XPath operator "div" (flipped into "vid") to avoid false positives
		$expr = strrev(preg_replace('(\\((?!\\s*(?!vid(?!\\w))\\w))', ' ', strrev($expr)));
		$expr = str_replace(')', ' ', $expr);
		if (preg_match('(^\\s*([$@][-\\w]++|-?\\.\\d++|-?\\d++(?:\\.\\d++)?)(?>\\s*(?>[-+*]|div)\\s*(?1))++\\s*$)', $expr))
		{
			return true;
		}

		return false;
	}

	/**
	* Remove extraneous space in a given XPath expression
	*
	* @param  string $expr Original XPath expression
	* @return string       Minified XPath expression
	*/
	public static function minify($expr)
	{
		$old     = $expr;
		$strings = [];

		// Trim the surrounding whitespace then temporarily remove literal strings
		$expr = preg_replace_callback(
			'/"[^"]*"|\'[^\']*\'/',
			function ($m) use (&$strings)
			{
				$uniqid = '(' . sha1(uniqid()) . ')';
				$strings[$uniqid] = $m[0];

				return $uniqid;
			},
			trim($expr)
		);

		if (preg_match('/[\'"]/', $expr))
		{
			throw new RuntimeException("Cannot parse XPath expression '" . $old . "'");
		}

		// Normalize whitespace to a single space
		$expr = preg_replace('/\\s+/', ' ', $expr);

		// Remove the space between a non-word character and a word character
		$expr = preg_replace('/([-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = preg_replace('/([^-a-z_0-9]) ([-a-z_0-9])/i', '$1$2', $expr);

		// Remove the space between two non-word characters as long as they're not two -
		$expr = preg_replace('/(?!- -)([^-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);

		// Remove the space between a - and a word character, as long as there's a space before -
		$expr = preg_replace('/ - ([a-z_0-9])/i', ' -$1', $expr);

		// Remove the spaces between a number and the div operator and the next token
		$expr = preg_replace('/((?:^|[ \\(])\\d+) div ?/', '$1div', $expr);

		// Remove the space between the div operator the next token
		$expr = preg_replace('/([^-a-z_0-9]div) (?=[$0-9@])/', '$1', $expr);

		// Restore the literals
		$expr = strtr($expr, $strings);

		return $expr;
	}

	/**
	* Parse an XPath expression that is composed entirely of equality tests between a variable part
	* and a constant part
	*
	* @param  string      $expr
	* @return array|false
	*/
	public static function parseEqualityExpr($expr)
	{
		// Match an equality between a variable and a literal or the concatenation of strings
		$eq = '(?<equality>'
		    . '(?<key>@[-\\w]+|\\$\\w+|\\.)'
		    . '(?<operator>\\s*=\\s*)'
		    . '(?:'
		    . '(?<literal>(?<string>"[^"]*"|\'[^\']*\')|0|[1-9][0-9]*)'
		    . '|'
		    . '(?<concat>concat\\(\\s*(?&string)\\s*(?:,\\s*(?&string)\\s*)+\\))'
		    . ')'
		    . '|'
		    . '(?:(?<literal>(?&literal))|(?<concat>(?&concat)))(?&operator)(?<key>(?&key))'
		    . ')';

		// Match a string that is entirely composed of equality checks separated with "or"
		$regexp = '(^(?J)\\s*' . $eq . '\\s*(?:or\\s*(?&equality)\\s*)*$)';

		if (!preg_match($regexp, $expr))
		{
			return false;
		}

		preg_match_all("((?J)$eq)", $expr, $matches, PREG_SET_ORDER);

		$map = [];
		foreach ($matches as $m)
		{
			$key = $m['key'];
			if (!empty($m['concat']))
			{
				preg_match_all('(\'[^\']*\'|"[^"]*")', $m['concat'], $strings);

				$value = '';
				foreach ($strings[0] as $string)
				{
					$value .= substr($string, 1, -1);
				}
			}
			else
			{
				$value = $m['literal'];
				if ($value[0] === "'" || $value[0] === '"')
				{
					$value = substr($value, 1, -1);
				}
			}

			$map[$key][] = $value;
		}

		return $map;
	}
}