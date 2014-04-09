<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Validators\AttributeName;

class AttributeCollection extends NormalizedCollection
{
	/**
	* Normalize a key as an attribute name
	*
	* @param  string $key
	* @return string
	*/
	public function normalizeKey($key)
	{
		return AttributeName::normalize($key);
	}

	/**
	* Normalize a value to an instance of Attribute
	*
	* @param  array|null|Attribute $value
	* @return Attribute
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof Attribute)
		     ? $value
		     : new Attribute($value);
	}
}