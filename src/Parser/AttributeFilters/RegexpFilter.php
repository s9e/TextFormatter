<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser\AttributeFilters;

class RegexpFilter
{
	/**
	* Filter a value by regexp
	*
	* @param  string $attrValue Original value
	* @param  string $regexp    Filtering regexp
	* @return mixed             Filtered value, or FALSE if invalid
	*/
	public static function filter($attrValue, $regexp)
	{
		return filter_var($attrValue, FILTER_VALIDATE_REGEXP, [
			'options' => ['regexp' => $regexp]
		]);
	}
}