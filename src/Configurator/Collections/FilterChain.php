<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\FilterHelper;
use s9e\TextFormatter\Configurator\Items\Filter;
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
	* @param  mixed  $value Either a valid callback or an instance of TagFilter
	* @return Filter        Normalized filter
	*/
	public function normalizeValue($value)
	{
		$className = $this->getFilterClassName();
		if ($value instanceof $className)
		{
			return $value;
		}

		if (is_callable($value))
		{
			return new $className($value);
		}

		if (is_string($value))
		{
			return $this->createFilter($value);
		}

		throw new InvalidArgumentException('Filter ' . var_export($value, true) . ' is neither callable nor an instance of ' . $className);
	}

	protected function createDefaultFilter(string $filterString)
	{
		$config = FilterHelper::parse($filterString);

		$constructorArgs = $this->getConstructorArgsFromFilterParams($config['params'] ?? []);
		$filterName      = substr($config['filter'], 1);

		return $this->getDefaultFilter($filterName, $constructorArgs);
	}

	/**
	* Create and return a filter
	*
	* @param  string $filterString
	* @return Filter
	*/
	protected function createFilter($filterString)
	{
		if ($filterString[0] === '#')
		{
			return $this->createDefaultFilter($filterString);
		}

		$config = FilterHelper::parse($filterString);
		$filter = $this->normalizeValue($config['filter']);
		if (isset($config['params']))
		{
			$filter->resetParameters();
			foreach ($config['params'] as [$type, $value])
			{
				$methodName = 'addParameterBy' . $type;
				$filter->$methodName($value);
			}
		}

		return $filter;
	}

	protected function getConstructorArgsFromFilterParams(array $params): array
	{
		$constructorArgs = [];
		foreach ($params as $i => $param)
		{
			[$type, $value] = $param;
			if ($type !== 'Value')
			{
				throw new RuntimeException('Cannot use default filter with named parameters');
			}

			if (isset($param[2]))
			{
				// Named argument
				$constructorArgs[$param[2]] = $value;
			}
			else
			{
				// Positional argument
				$constructorArgs[] = $value;
			}
		}

		return $constructorArgs;
	}

	/**
	* Get an instance of a default filter
	*/
	protected function getDefaultFilter(string $filterName, array $constructorArgs = []): Filter
	{
		throw new RuntimeException(get_class($this) . ' has no default filters');
	}
}