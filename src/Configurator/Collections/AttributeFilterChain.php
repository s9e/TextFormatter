<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class AttributeFilterChain extends FilterChain
{
	/**
	* Normalize a value into an AttributeFilter instance
	*
	* @param  mixed           $value Either a valid callback or an instance of AttributeFilter
	* @return AttributeFilter        Normalized filter
	*/
	public function normalizeValue($value)
	{
		if ($value instanceof AttributeFilter)
		{
			return $value;
		}

		if (!is_callable($value))
		{
			throw new InvalidArgumentException('Filter ' . var_export($value, true) . ' is neither callable nor an instance of AttributeFilter');
		}

		return new AttributeFilter($value);
	}
}