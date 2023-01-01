<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser\AttributeFilters;

class FalseFilter
{
	/**
	* Invalidate an attribute value
	*
	* @param  string $attrValue Original value
	* @return bool              Always FALSE
	*/
	public static function filter($attrValue)
	{
		return false;
	}
}