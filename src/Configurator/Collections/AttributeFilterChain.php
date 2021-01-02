<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

class AttributeFilterChain extends FilterChain
{
	/**
	* {@inheritdoc}
	*/
	public function getFilterClassName()
	{
		return 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilter';
	}

	/**
	* Normalize a value into an AttributeFilter instance
	*
	* @param  mixed $value Either a valid callback or an instance of AttributeFilter
	* @return \s9e\TextFormatter\Configurator\Items\AttributeFilter Normalized filter
	*/
	public function normalizeValue($value)
	{
		if (is_string($value) && preg_match('(^#\\w+$)', $value))
		{
			$value = AttributeFilterCollection::getDefaultFilter(substr($value, 1));
		}

		return parent::normalizeValue($value);
	}
}