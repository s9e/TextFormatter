<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils;

use InvalidArgumentException;

abstract class XPath
{
	/**
	* Export a literal as an XPath expression
	*
	* @param  mixed  $value Literal, e.g. "foo"
	* @return string        XPath expression, e.g. "'foo'"
	*/
	public static function export($value)
	{
		$callback = get_called_class() . '::export' . ucfirst(gettype($value));
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException(__METHOD__ . '() cannot export non-scalar values');
		}

		return $callback($value);
	}

	/**
	* Export given boolean value
	*
	* @param  bool   $value
	* @return string
	*/
	protected static function exportBoolean(bool $value): string
	{
		return ($value) ? 'true()' : 'false()';
	}

	/**
	* Export given float value
	*
	* @param  float  $value
	* @return string
	*/
	protected static function exportDouble(float $value): string
	{
		if (!is_finite($value))
		{
			throw new InvalidArgumentException(__METHOD__ . '() cannot export irrational numbers');
		}

		// Avoid locale issues by using sprintf()
		return preg_replace('(\\.?0+$)', '', sprintf('%F', $value));
	}

	/**
	* Export given integer value
	*
	* @param  integer $value
	* @return string
	*/
	protected static function exportInteger(int $value): string
	{
		return (string) $value;
	}

	/**
	* Export a string as an XPath expression
	*
	* @param  string $str Literal, e.g. "foo"
	* @return string      XPath expression, e.g. "'foo'"
	*/
	protected static function exportString(string $str): string
	{
		// foo becomes 'foo'
		if (strpos($str, "'") === false)
		{
			return "'" . $str . "'";
		}

		// d'oh becomes "d'oh"
		if (strpos($str, '"') === false)
		{
			return '"' . $str . '"';
		}

		// This string contains both ' and ". XPath 1.0 doesn't have a mechanism to escape quotes,
		// so we have to get creative and use concat() to join chunks in single quotes and chunks
		// in double quotes
		$toks = [];
		$c    = '"';
		$pos  = 0;
		while ($pos < strlen($str))
		{
			$spn = strcspn($str, $c, $pos);
			if ($spn)
			{
				$toks[] = $c . substr($str, $pos, $spn) . $c;
				$pos   += $spn;
			}
			$c = ($c === '"') ? "'" : '"';
		}

		return 'concat(' . implode(',', $toks) . ')';
	}
}