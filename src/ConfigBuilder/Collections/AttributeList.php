<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Validators\AttributeName;

/**
* Hosts a list of attribute names. The names are deduplicated when the config array is built
*/
class AttributeList extends NormalizedList
{
	/**
	* {@inheritdoc}
	*/
	public function toConfig()
	{
		return array_unique($this->items);
	}

	/**
	* Normalize an attribute name for storage
	*
	* @param  string $attrName Original name
	* @return void             Normalized name
	*/
	public function normalizeValue($attrName)
	{
		return AttributeName::normalize($attrName);
	}
}