<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use Exception;

abstract class Minifier
{
	public $cacheDir;

	public $keepGoing = \false;

	public function get($src)
	{
		try
		{
			if (isset($this->cacheDir))
			{
				$differentiator = $this->getCacheDifferentiator();

				if ($differentiator !== \false)
				{
					$key       = \sha1(\serialize(array(\get_class($this), $differentiator, $src)));
					$cacheFile = $this->cacheDir . '/minifier.' . $key . '.js';

					if (\file_exists($cacheFile))
						return \file_get_contents($cacheFile);
				}
			}

			$src = $this->minify($src);

			if (isset($cacheFile))
				\file_put_contents($cacheFile, $src);
		}
		catch (Exception $e)
		{
			if (!$this->keepGoing)
				throw $e;
		}

		return $src;
	}

	public function getCacheDifferentiator()
	{
		return \false;
	}

	abstract public function minify($src);
}