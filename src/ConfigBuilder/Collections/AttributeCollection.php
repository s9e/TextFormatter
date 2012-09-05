<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Items\Attribute;
use s9e\TextFormatter\ConfigBuilder\Validators\AttributeName;

class AttributeCollection extends NormalizedCollection
{
	public function normalizeKey($key)
	{
		return AttributeName::normalize($key);
	}

	public function normalizeValue($value)
	{
		return ($value instanceof Attribute)
		     ? $value
		     : new Attribute($value);
	}
}