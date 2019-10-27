<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils;
use InvalidArgumentException;
abstract class XPath
{
	public static function export($value)
	{
		$callback = \get_called_class() . '::export' . \ucfirst(\gettype($value));
		if (!\is_callable($callback))
			throw new InvalidArgumentException(__METHOD__ . '() cannot export non-scalar values');
		return $callback($value);
	}
	protected static function exportBoolean(bool $value): string
	{
		return ($value) ? 'true()' : 'false()';
	}
	protected static function exportDouble(float $value): string
	{
		if (!\is_finite($value))
			throw new InvalidArgumentException(__METHOD__ . '() cannot export irrational numbers');
		return \preg_replace('(\\.?0+$)', '', \sprintf('%F', $value));
	}
	protected static function exportInteger(int $value): string
	{
		return (string) $value;
	}
	protected static function exportString(string $str): string
	{
		if (\strpos($str, "'") === \false)
			return "'" . $str . "'";
		if (\strpos($str, '"') === \false)
			return '"' . $str . '"';
		$toks = array();
		$c    = '"';
		$pos  = 0;
		while ($pos < \strlen($str))
		{
			$spn = \strcspn($str, $c, $pos);
			if ($spn)
			{
				$toks[] = $c . \substr($str, $pos, $spn) . $c;
				$pos   += $spn;
			}
			$c = ($c === '"') ? "'" : '"';
		}
		return 'concat(' . \implode(',', $toks) . ')';
	}
}