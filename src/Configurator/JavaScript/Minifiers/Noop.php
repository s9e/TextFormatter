<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use s9e\TextFormatter\Configurator\JavaScript\Minifier;

/**
* No-op minifier
*/
class Noop extends Minifier
{
	/**
	* No-op method, output is the same as input
	*
	* @param  string $src JavaScript source
	* @return string      The very same JavaScript source
	*/
	public function minify($src)
	{
		return $src;
	}
}