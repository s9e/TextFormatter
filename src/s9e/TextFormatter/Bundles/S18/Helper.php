<?php
/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/

namespace s9e\TextFormatter\Bundles\S18;

use s9e\TextFormatter\Parser\BuiltInFilters;
use s9e\TextFormatter\Parser\Logger;

abstract class Helper
{
	/**
	* Prepend the http:// scheme in front of a URL if it's not already present and it doesn't start
	* with a #, and validate as a URL if it doesn't start with #
	*
	* @param  string $url       Original URL
	* @param  array  $urlConfig Config used by the URL filter
	* @param  Logger $logger    Default logger
	* @return mixed             Original value if valid, FALSE otherwise
	*/
	public static function filterIurl($url, array $urlConfig, Logger $logger)
	{
		// Anchor links are returned as-is
		if (substr($url, 0, 1) === '#')
		{
			return $url;
		}

		// Prepend http:// if applicable
		$url = self::prependHttp($url);

		// Validate as a URL
		return BuiltInFilters::filterUrl($url, $urlConfig, $logger);
	}

	/**
	* Prepend the ftp:// scheme in front of a URL if it's not already present
	*
	* @param  string $url Original URL
	* @return string      URL that starts with ftp:// or ftps://
	*/
	public static function prependFtp($url)
	{
		if (substr($url, 0, 6) !== 'ftp://'
		 && substr($url, 0, 7) !== 'ftps://')
		{
			 return 'ftp://' . $url;
		}

		return $url;
	}

	/**
	* Prepend the http:// scheme in front of a URL if it's not already present
	*
	* @param  string $url Original URL
	* @return string      URL that starts with http:// or https://
	*/
	public static function prependHttp($url)
	{
		if (substr($url, 0, 7) !== 'http://'
		 && substr($url, 0, 8) !== 'https://')
		{
			 return 'http://' . $url;
		}

		return $url;
	}

	/**
	* Format timestamps inside of an XML representation
	*
	* @param  string   $xml      XML representation of a parsed text
	* @param  callback $callback Formatting callback, will be passed the timestamp as a number
	* @return string             XML representation, with human-readable dates
	*/
	public static function timeformat($xml, $callback = 'timeformat')
	{
		return preg_replace_callback(
			'/(<(?:QUOT|TIM)E [^>]*?(?:dat|tim)e=")(\\d+)(?=")/',
			function ($m) use ($callback)
			{
				return $m[1] . htmlspecialchars($callback($m[2]), ENT_COMPAT);
			},
			$xml
		);
	}
}