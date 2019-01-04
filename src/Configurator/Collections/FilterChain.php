<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;

abstract class FilterChain extends NormalizedList
{
	/**
	* Get the name of the filter class
	*
	* @return string
	*/
	abstract protected function getFilterClassName();

	/**
	* Test whether this filter chain contains given callback
	*
	* @param  callable $callback
	* @return bool
	*/
	public function containsCallback(callable $callback)
	{
		// Normalize the callback
		$pc = new ProgrammableCallback($callback);
		$callback = $pc->getCallback();
		foreach ($this->items as $filter)
		{
			if ($callback === $filter->getCallback())
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Normalize a value into an TagFilter instance
	*
	* @param  mixed     $value Either a valid callback or an instance of TagFilter
	* @return TagFilter        Normalized filter
	*/
	public function normalizeValue($value)
	{
		$className  = $this->getFilterClassName();
		if ($value instanceof $className)
		{
			return $value;
		}

		if (!is_callable($value))
		{
			throw new InvalidArgumentException('Filter ' . var_export($value, true) . ' is neither callable nor an instance of ' . $className);
		}

		return new $className($value);
	}
}