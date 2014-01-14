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
	* {@inheritdoc}
	*/
	public function normalizeKey($key)
	{
		return AttributeName::normalize($key);
	}

	/**
	* {@inheritdoc}
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof Attribute)
		     ? $value
		     : new Attribute($value);
	}
}