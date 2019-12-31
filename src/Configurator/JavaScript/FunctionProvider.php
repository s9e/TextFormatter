<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use InvalidArgumentException;

class FunctionProvider
{
	/**
	* @param array Function name as keys, JavaScript source as values
	*/
	public static $cache = [
		'addslashes' => 'function(str)
{
	return str.replace(/["\'\\\\]/g, \'\\\\$&\').replace(/\\u0000/g, \'\\\\0\');
}',
		'dechex' => 'function(str)
{
	return parseInt(str).toString(16);
}',
		'intval' => 'function(str)
{
	return parseInt(str) || 0;
}',
		'ltrim' => 'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\');
}',
		'mb_strtolower' => 'function(str)
{
	return str.toLowerCase();
}',
		'mb_strtoupper' => 'function(str)
{
	return str.toUpperCase();
}',
		'mt_rand' => 'function(min, max)
{
	return (min + Math.floor(Math.random() * (max + 1 - min)));
}',
		'rawurlencode' => 'function(str)
{
	return encodeURIComponent(str).replace(
		/[!\'()*]/g,
		/**
		* @param {string} c
		*/
		function(c)
		{
			return \'%\' + c.charCodeAt(0).toString(16).toUpperCase();
		}
	);
}',
		'rtrim' => 'function(str)
{
	return str.replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'str_rot13' => 'function(str)
{
	return str.replace(
		/[a-z]/gi,
		function(c)
		{
			return String.fromCharCode(c.charCodeAt(0) + ((c.toLowerCase() < \'n\') ? 13 : -13));
		}
	);
}',
		'stripslashes' => 'function(str)
{
	// NOTE: this will not correctly transform \\0 into a NULL byte. I consider this a feature
	//       rather than a bug. There\'s no reason to use NULL bytes in a text.
	return str.replace(/\\\\([\\s\\S]?)/g, \'\\\\1\');
}',
		'strrev' => 'function(str)
{
	return str.split(\'\').reverse().join(\'\');
}',
		'strtolower' => 'function(str)
{
	return str.toLowerCase();
}',
		'strtotime' => 'function(str)
{
	return Date.parse(str) / 1000;
}',
		'strtoupper' => 'function(str)
{
	return str.toUpperCase();
}',
		'trim' => 'function(str)
{
	return str.replace(/^[ \\n\\r\\t\\0\\x0B]+/g, \'\').replace(/[ \\n\\r\\t\\0\\x0B]+$/g, \'\');
}',
		'ucfirst' => 'function(str)
{
	return str[0].toUpperCase() + str.substr(1);
}',
		'ucwords' => 'function(str)
{
	return str.replace(
		/(?:^|\\s)[a-z]/g,
		function(m)
		{
			return m.toUpperCase()
		}
	);
}',
		'urldecode' => 'function(str)
{
	return decodeURIComponent("" + str);
}',
		'urlencode' => 'function(str)
{
	return encodeURIComponent(str);
}'
	];

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