<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;
use s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanFunctions;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\BooleanOperators;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Comparisons;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Core;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\Math;
use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors\SingleByteStringFunctions;
use s9e\TextFormatter\Utils\XPath;

abstract class XPathHelper
{
	/**
	* Decode strings inside of an XPath expression
	*
	* @param  string $expr
	* @return string
	*/
	public static function decodeStrings($expr)
	{
		return preg_replace_callback(
			'(\'[^\']*+\'|"[^"]*+")',
			function ($m)
			{
				return $m[0][0] . hex2bin(substr($m[0], 1, -1)) . $m[0][0];
			},
			$expr
		);
	}

	/**
	* Encode strings inside of an XPath expression
	*
	* @param  string $expr
	* @return string
	*/
	public static function encodeStrings($expr)
	{
		return preg_replace_callback(
			'(\'[^\']*+\'|"[^"]*+")',
			function ($m)
			{
				return $m[0][0] . bin2hex(substr($m[0], 1, -1)) . $m[0][0];
			},
			$expr
		);
	}

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
		// Detect simple arithmetic operations
		if (preg_match('(^([$@][-\\w]++|-?[.\\d]++)(?: *(?:[-*+]|div) *(?1))+$)', $expr))
		{
			return true;
		}

		// Try parsing the expression as a math expression
		try
		{
			return (bool) self::getXPathParser()->parse($expr, 'Math');
		}
		catch (RuntimeException $e)
		{
			// Do nothing
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
		$expr = trim($expr);

		// Test whether there's any characters that can be removed
		if (!preg_match('([\\s\\)])', $expr))
		{
			return $expr;
		}

		// Temporarily encode the content of literal strings
		$expr = self::encodeStrings(trim($expr));

		// Normalize whitespace to a single space
		$expr = preg_replace('(\\s+)', ' ', $expr);

		$regexps = [
			// Remove the space between a non-word character and a word character
			'([-a-z_0-9]\\K (?=[^-a-z_0-9]))i',
			'([^-a-z_0-9]\\K (?=[-a-z_0-9]))i',

			// Remove the space between two non-word characters as long as they're not two -
			'((?!- -)[^-a-z_0-9]\\K (?=[^-a-z_0-9]))i',

			// Remove the space between a - and a word character as long as there's a space before -
			'( -\\K (?=[a-z_0-9]))i',

			// Remove the space between an operator and the next token if it's a left parenthesis
			'([ \\)](?:and|div|mod|or)\\K (?=\\())',

			// Remove the space after a number
			'(\\b\\d+\\K )'
		];
		$expr = preg_replace($regexps, '', $expr);

		// Remove consecutive parentheses where redundant
		$expr = self::removeRedundantParentheses($expr);

		// Restore the literals
		$expr = self::decodeStrings($expr);

		return $expr;
	}

	/**
	* Remove consecutive parentheses where redundant
	*/
	protected static function removeRedundantParentheses(string $expr): string
	{
		// Add parentheses around the original expression and terminate the expression with a space
		preg_match_all('(([\\(\\)])|[^\\(\\)]++)', '(' . $expr . ') ', $m);
		$tokens = $m[0];
		$parens = array_filter($m[1]);

		// Iterate over parentheses and remove the inner pair when consecutive parentheses are found
		$depth = 0;
		$left  = [];
		foreach ($parens as $k => $token)
		{
			if ($token === '(')
			{
				$left[$depth++] = $k;
			}
			elseif (--$depth > 0 && $tokens[$k + 1] === ')' && $left[$depth - 1] === $left[$depth] - 1)
			{
				unset($tokens[$k], $tokens[$left[$depth]]);
			}
		}

		// Remove the extra parentheses as well as the last token before serializing them
		return implode('', array_slice($tokens, 1, -2));
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
			$key   = $m['key'];
			$value = (!empty($m['concat']))
			       ? self::evaluateConcat($m['concat'])
			       : self::evaluateLiteral($m['literal']);

			$map[$key][] = $value;
		}

		return $map;
	}

	/**
	* Evaluate a concat() expression where all arguments are string literals
	*
	* @param  string $expr concat() expression
	* @return string       Expression's value
	*/
	protected static function evaluateConcat($expr)
	{
		preg_match_all('(\'[^\']*\'|"[^"]*")', $expr, $strings);

		$value = '';
		foreach ($strings[0] as $string)
		{
			$value .= substr($string, 1, -1);
		}

		return $value;
	}

	/**
	* Evaluate an XPath literal
	*
	* @param  string $expr XPath literal
	* @return string       Literal's string value
	*/
	protected static function evaluateLiteral($expr)
	{
		if ($expr[0] === '"' || $expr[0] === "'")
		{
			$expr = substr($expr, 1, -1);
		}

		return $expr;
	}

	/**
	* Generate and return a cached XPath parser with a default set of matchers
	*
	* @return RecursiveParser
	*/
	protected static function getXPathParser()
	{
		static $parser;
		if (!isset($parser))
		{
			$parser     = new RecursiveParser;
			$matchers   = [];
			$matchers[] = new BooleanFunctions($parser);
			$matchers[] = new BooleanOperators($parser);
			$matchers[] = new Comparisons($parser);
			$matchers[] = new Core($parser);
			$matchers[] = new Math($parser);
			$matchers[] = new SingleByteStringFunctions($parser);

			$parser->setMatchers($matchers);
		}

		return $parser;
	}
}