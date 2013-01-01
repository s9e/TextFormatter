<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Javascript;

interface Minifier
{
	/**
	* Minify given Javascript source
	*
	* @param  string $src Javascript source
	* @return string      Minified source
	*/
	public function minify($src);
}