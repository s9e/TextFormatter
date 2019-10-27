<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use RuntimeException;
use s9e\TextFormatter\Utils\XPath;
abstract class XPathHelper
{
	public static function decodeStrings($expr)
	{
		return \preg_replace_callback(
			'(([\'"])(.*?)\\1)s',
			function ($m)
			{
				return $m[1] . \hex2bin($m[2]) . $m[1];
			},
			$expr
		);
	}
	public static function encodeStrings($expr)
	{
		return \preg_replace_callback(
			'(([\'"])(.*?)\\1)s',
			function ($m)
			{
				return $m[1] . \bin2hex($m[2]) . $m[1];
			},
			$expr
		);
	}
	public static function getVariables($expr)
	{
		$expr = \preg_replace('/(["\']).*?\\1/s', '$1$1', $expr);
		\preg_match_all('/\\$(\\w+)/', $expr, $matches);
		$varNames = \array_unique($matches[1]);
		\sort($varNames);
		return $varNames;
	}
	public static function isExpressionNumeric($expr)
	{
		$expr = \strrev(\preg_replace('(\\((?!\\s*(?!vid(?!\\w))\\w))', ' ', \strrev($expr)));
		$expr = \str_replace(')', ' ', $expr);
		if (\preg_match('(^\\s*([$@][-\\w]++|-?\\.\\d++|-?\\d++(?:\\.\\d++)?)(?>\\s*(?>[-+*]|div)\\s*(?1))++\\s*$)', $expr))
			return \true;
		return \false;
	}
	public static function minify($expr)
	{
		$old     = $expr;
		$strings = array();
		$expr = \preg_replace_callback(
			'/"[^"]*"|\'[^\']*\'/',
			function ($m) use (&$strings)
			{
				$uniqid = '(' . \sha1(\uniqid()) . ')';
				$strings[$uniqid] = $m[0];
				return $uniqid;
			},
			\trim($expr)
		);
		if (\preg_match('/[\'"]/', $expr))
			throw new RuntimeException("Cannot parse XPath expression '" . $old . "'");
		$expr = \preg_replace('/\\s+/', ' ', $expr);
		$expr = \preg_replace('/([-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/([^-a-z_0-9]) ([-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/(?!- -)([^-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = \preg_replace('/ - ([a-z_0-9])/i', ' -$1', $expr);
		$expr = \preg_replace('/((?:^|[ \\(])\\d+) div ?/', '$1div', $expr);
		$expr = \preg_replace('/([^-a-z_0-9]div) (?=[$0-9@])/', '$1', $expr);
		$expr = \strtr($expr, $strings);
		return $expr;
	}
	public static function parseEqualityExpr($expr)
	{
		$eq = '(?<equality>(?<key>@[-\\w]+|\\$\\w+|\\.)(?<operator>\\s*=\\s*)(?:(?<literal>(?<string>"[^"]*"|\'[^\']*\')|0|[1-9][0-9]*)|(?<concat>concat\\(\\s*(?&string)\\s*(?:,\\s*(?&string)\\s*)+\\)))|(?:(?<literal>(?&literal))|(?<concat>(?&concat)))(?&operator)(?<key>(?&key)))';
		$regexp = '(^(?J)\\s*' . $eq . '\\s*(?:or\\s*(?&equality)\\s*)*$)';
		if (!\preg_match($regexp, $expr))
			return \false;
		\preg_match_all("((?J)$eq)", $expr, $matches, \PREG_SET_ORDER);
		$map = array();
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
	protected static function evaluateConcat($expr)
	{
		\preg_match_all('(\'[^\']*\'|"[^"]*")', $expr, $strings);
		$value = '';
		foreach ($strings[0] as $string)
			$value .= \substr($string, 1, -1);
		return $value;
	}
	protected static function evaluateLiteral($expr)
	{
		if ($expr[0] === '"' || $expr[0] === "'")
			$expr = \substr($expr, 1, -1);
		return $expr;
	}
}