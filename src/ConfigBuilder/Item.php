<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

interface Item
{
	/**
	* Validate and normalize this item's name
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	static public function normalizeName($name);
}