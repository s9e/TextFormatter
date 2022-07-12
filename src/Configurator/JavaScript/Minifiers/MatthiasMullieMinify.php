<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;

use MatthiasMullie\Minify;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;

/**
* @link http://www.minifier.org/
*/
class MatthiasMullieMinify extends Minifier
{
	/**
	* Compile given JavaScript source using matthiasmullie/minify
	*
	* @param  string $src JavaScript source
	* @return string      Compiled source
	*/
	public function minify($src)
	{
		$minifier = new Minify\JS($src);

		return $minifier->minify();
	}
}