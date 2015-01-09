<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;

abstract class XPathHelper
{
	public static function export($str)
	{
		if (\strpos($str, "'") === \false)
			return "'" . $str . "'";

		if (\strpos($str, '"') === \false)
			return '"' . $str . '"';

		$toks = array();
		$c = '"';
		$pos = 0;
		while ($pos < \strlen($str))
		{
			$spn = \strcspn($str, $c, $pos);
			if ($spn)
			{
				$toks[] = $c . \substr($str, $pos, $spn) . $c;
				$pos += $spn;
			}
			$c = ($c === '"') ? "'" : '"';
		}

		return 'concat(' . \implode(',', $toks) . ')';
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
		$expr = \trim($expr);
		$expr = \strrev(\preg_replace('(\\((?!\\s*\\w))', '', \strrev($expr)));
		$expr = \str_replace(')', '', $expr);

		if (\preg_match('(^([$@][-\\w]++|-?\\d++)(?>\\s*(?>[-+*]|div)\\s*(?1))++$)', $expr))
			return \true;

		return \false;
	}

	public static function minify($expr)
	{
		$old     = $expr;
		$strings = array();

		$expr = \preg_replace_callback(
			'/(?:"[^"]*"|\'[^\']*\')/',
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

		$expr = \strtr($expr, $strings);

		return $expr;
	}
}