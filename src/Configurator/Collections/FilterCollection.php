<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\Filter;

class FilterCollection extends NormalizedCollection
{
	/**
	* {@inheritdoc}
	*/
	public function normalizeValue($value)
	{
		if (!($value instanceof Filter))
		{
			throw new InvalidArgumentException('Not an instance of s9e\\TextFormatter\\Configurator\\Items\\Filter');
		}

		return $value;
	}
}