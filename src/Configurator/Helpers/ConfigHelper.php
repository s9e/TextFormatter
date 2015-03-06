<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;

abstract class ConfigHelper
{
	public static function filterVariants(&$config, $variant = \null)
	{
		foreach ($config as $name => $value)
		{
			while ($value instanceof Variant)
			{
				$value = $value->get($variant);

				if ($value === \null)
				{
					unset($config[$name]);

					continue 2;
				}
			}

			if ($value instanceof Dictionary && $variant !== 'JS')
				$value = (array) $value;

			if (\is_array($value) || $value instanceof Traversable)
				self::filterVariants($value, $variant);

			$config[$name] = $value;
		}
	}

	public static function generateQuickMatchFromList(array $strings)
	{
		foreach ($strings as $string)
		{
			$stringLen  = \strlen($string);
			$substrings = [];

			for ($len = $stringLen; $len; --$len)
			{
				$pos = $stringLen - $len;

				do
				{
					$substrings[\substr($string, $pos, $len)] = 1;
				}
				while (--$pos >= 0);
			}

			if (isset($goodStrings))
			{
				$goodStrings = \array_intersect_key($goodStrings, $substrings);

				if (empty($goodStrings))
					break;
			}
			else
				$goodStrings = $substrings;
		}

		if (empty($goodStrings))
			return \false;

		return \strval(\key($goodStrings));
	}

	public static function optimizeArray(array &$config, array &$cache = [])
	{
		foreach ($config as $k => &$v)
		{
			if (!\is_array($v))
				continue;

			self::optimizeArray($v, $cache);

			$cacheKey = \array_search($v, $cache);
			if ($cacheKey === \false)
			{
				$cacheKey         = \count($cache);
				$cache[$cacheKey] = $v;
			}

			$config[$k] =& $cache[$cacheKey];
		}
		unset($v);
	}

	public static function toArray($value, $keepEmpty = \false, $keepNull = \false)
	{
		$array = [];

		foreach ($value as $k => $v)
		{
			if ($v instanceof ConfigProvider)
				$v = $v->asConfig();
			elseif ($v instanceof Traversable || \is_array($v))
				$v = self::toArray($v, $keepEmpty, $keepNull);
			elseif (\is_scalar($v) || \is_null($v))
				;
			else
			{
				$type = (\is_object($v))
				      ? 'an instance of ' . \get_class($v)
				      : 'a ' . \gettype($v);

				throw new RuntimeException('Cannot convert ' . $type . ' to array');
			}

			if (!isset($v) && !$keepNull)
				continue;

			if (!$keepEmpty && $v === [])
				continue;

			$array[$k] = $v;
		}

		return $array;
	}
}