<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Javascript\Minifiers;

use s9e\TextFormatter\Configurator\Javascript\Minifier;

/**
* No-op minifier
*/
class Noop implements Minifier
{
	/**
	* No-op method, output is the same as input
	*
	* @param  string $src Javascript source
	* @return string      The very same Javascript source
	*/
	public function minify($src)
	{
		return $src;
	}
}