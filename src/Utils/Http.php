<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Utils;

use s9e\TextFormatter\Utils\Http\Clients\Cached;
use s9e\TextFormatter\Utils\Http\Clients\Curl;
use s9e\TextFormatter\Utils\Http\Clients\Native;

abstract class Http
{
	/**
	* Instantiate and return an HTTP client
	*
	* @return Http\Client
	*/
	public static function getClient()
	{
		return (extension_loaded('curl')) ? new Curl : new Native;
	}
	/**
	* Instantiate and return a caching HTTP client
	*
	* @param  string $cacheDir
	* @return Cached
	*/
	public static function getCachingClient($cacheDir = null)
	{
		$client = new Cached(self::getClient());
		$client->cacheDir = (isset($cacheDir)) ? $cacheDir : sys_get_temp_dir();

		return $client;
	}
}