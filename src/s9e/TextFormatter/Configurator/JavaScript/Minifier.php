<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
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
	* Minify given JavaScript source and cache the result if applicable
	*
	* @param  string $src JavaScript source
	* @return string      Minified source
	*/
	public function get($src)
	{
		try
		{
			// Check the cache for a hit, if applicable
			if (isset($this->cacheDir))
			{
				$differentiator = $this->getCacheDifferentiator();

				if ($differentiator !== false)
				{
					$key       = sha1(serialize([get_class($this), $differentiator, $src]));
					$cacheFile = $this->cacheDir . '/minifier.' . $key . '.js';

					if (file_exists($cacheFile))
					{
						return file_get_contents($cacheFile);
					}
				}
			}

			// Minify the source
			$src = $this->minify($src);

			// Cache the result if applicable
			if (isset($cacheFile))
			{
				file_put_contents($cacheFile, $src);
			}
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
	* Return a value that uniquely identify this minifier's configuration
	*
	* @return mixed Any value, or FALSE to disable caching
	*/
	public function getCacheDifferentiator()
	{
		return false;
	}

	/**
	* Minify given JavaScript source
	*
	* @param  string $src JavaScript source
	* @return string      Minified source
	*/
	abstract public function minify($src);
}