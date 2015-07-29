<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript\Minifiers;
use s9e\TextFormatter\Configurator\JavaScript\Minifier;
class Noop extends Minifier
{
	public function getCacheDifferentiator()
	{
		return \uniqid();
	}
	public function minify($src)
	{
		return $src;
	}
}