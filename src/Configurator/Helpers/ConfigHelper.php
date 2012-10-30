<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;
use Traversable;
use s9e\TextFormatter\Configurator\ConfigProvider;

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
			if (!isset($v))
			{
				// We don't record NULL values
				continue;
			}

			if ($v instanceof ConfigProvider)
			{
				$v = $v->asConfig();
			}
			elseif ($v instanceof Traversable || is_array($v))
			{
				$v = self::toArray($v);
			}
			elseif (!is_scalar($v))
			{
				$type = (is_object($v))
				      ? 'an instance of ' . get_class($v)
				      : 'a ' . gettype($v);

				throw new RuntimeException('Cannot convert ' . $type . ' to array');
			}

			if ($v === array())
			{
				// We don't record empty structures
				continue;
			}

			$array[$k] = $v;
		}

		return $array;
	}
}