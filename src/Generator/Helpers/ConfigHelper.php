<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator\Helpers;

use RuntimeException;
use Traversable;
use s9e\TextFormatter\Generator\ConfigProvider;

abstract class ConfigHelper
{
	/**
	* Convert a structure to a (possibly multidimensional) array
	*
	* @param  mixed $value
	* @return array
	*/
	public static function toArray($value)
	{
		$array = array();

		foreach ($value as $k => $v)
		{
			if ($v instanceof ConfigProvider)
			{
				$v = $v->toConfig();
			}
			elseif ($v instanceof Traversable || is_array($v))
			{
				$v = self::toArray($v);
			}
			elseif (!is_scalar($v))
			{
				throw new RuntimeException('Cannot convert ' . gettype($v) . ' to array');
			}

			$array[$k] = $v;
		}

		return $array;
	}
}