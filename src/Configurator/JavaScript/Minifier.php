<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use Exception;

abstract class Minifier
{
	/**
	* @var string Directory in which minified sources are cached
	*/
	public $cacheDir;

	/**
	* @var bool If TRUE, don't interrupt get() if an exception is thrown. Instead, return the original source
	*/
	public $keepGoing = false;

	/**
	* Return a value that uniquely identify this minifier's configuration
	*
	* @return array|string
	*/
	abstract public function getCacheDifferentiator();

	/**
	* Minify given JavaScript source
	*
	* @param  string $src JavaScript source
	* @return string      Minified source
	*/
	abstract public function minify($src);

	/**
	* Minify given JavaScript source and cache the result if applicable
	*
	* @param  string $src JavaScript source
	* @return string      Minified source
	*/
	public function get($src)
	{
		try
		{
			return (isset($this->cacheDir)) ? $this->getFromCache($src) : $this->minify($src);
		}
		catch (Exception $e)
		{
			if (!$this->keepGoing)
			{
				throw $e;
			}
		}

		return $src;
	}


	/**
	* Get the minified source from cache, or minify and cache the result
	*
	* @param  string $src JavaScript source
	* @return string      Minified source
	*/
	protected function getFromCache($src)
	{
		$differentiator = $this->getCacheDifferentiator();
		$key            = sha1(serialize([get_class($this), $differentiator, $src]));
		$cacheFile      = $this->cacheDir . '/minifier.' . $key . '.js';

		if (!file_exists($cacheFile))
		{
			file_put_contents($cacheFile, $this->minify($src));
		}

		return file_get_contents($cacheFile);
	}
}