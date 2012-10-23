<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser\Filters;

use s9e\TextFormatter\Parser\FilterBase;

class Number extends FilterBase
{
	/**
	* Filter a number
	*
	* @param  mixed $value Original value
	* @return mixed        Original value if valid, FALSE otherwise
	*/
	public static function filter($value)
	{
		return (preg_match('#^[0-9]+$#D', $number))
		     ? $number
		     : false;
	}
}