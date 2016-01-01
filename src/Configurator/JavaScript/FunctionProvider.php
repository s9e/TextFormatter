<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use InvalidArgumentException;

class FunctionProvider
{
	/**
	* @param array Function name as keys, JavaScript source as values
	*/
	public static $cache = [];

	/**
	* Return a function's source from the cache or the filesystem
	*
	* @param  string $funcName Function's name
	* @return string           Function's source
	*/
	public static function get($funcName)
	{
		if (isset(self::$cache[$funcName]))
		{
			return self::$cache[$funcName];
		}
		if (preg_match('(^[a-z_0-9]+$)D', $funcName))
		{
			$filepath = __DIR__ . '/functions/' . $funcName . '.js';
			if (file_exists($filepath))
			{
				return file_get_contents($filepath);
			}
		}
		throw new InvalidArgumentException("Unknown function '" . $funcName . "'");
	}
}