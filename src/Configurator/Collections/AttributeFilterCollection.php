<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;

class AttributeFilterCollection extends NormalizedCollection
{
	/**
	* Return a value from this collection
	*
	* @param  string $key
	* @return \s9e\TextFormatter\Configurator\Items\ProgrammableCallback
	*/
	public function get($key)
	{
		$key = $this->normalizeKey($key);

		if (!$this->exists($key))
		{
			if ($key[0] === '#')
			{
				$this->set($key, self::getDefaultFilter(substr($key, 1)));
			}
			else
			{
				$this->set($key, new AttributeFilter($key));
			}
		}

		// Get the filter from the collection
		$filter = parent::get($key);

		// Clone it to preserve the original instance
		$filter = clone $filter;

		return $filter;
	}

	/**
	* Get an instance of the default filter for given name
	*
	* @param  string          $filterName Filter name, e.g. "int" or "color"
	* @return AttributeFilter
	*/
	public static function getDefaultFilter($filterName)
	{
		$filterName = ucfirst(strtolower($filterName));
		$className  = 's9e\\TextFormatter\\Configurator\\Items\\AttributeFilters\\' . $filterName . 'Filter';

		if (!class_exists($className))
		{
			throw new InvalidArgumentException("Unknown attribute filter '" . $filterName . "'");
		}

		return new $className;
	}

	/**
	* Normalize the name of an attribute filter
	*
	* @param  string $key
	* @return string
	*/
	public function normalizeKey($key)
	{
		// Built-in/custom filter, normalized to lowercase
		if (preg_match('/^#[a-z_0-9]+$/Di', $key))
		{
			return strtolower($key);
		}

		// Valid callback
		if (is_string($key) && is_callable($key))
		{
			return $key;
		}

		throw new InvalidArgumentException("Invalid filter name '" . $key . "'");
	}

	/**
	* Normalize a value to an instance of AttributeFilter
	*
	* @param  callable|AttributeFilter $value
	* @return AttributeFilter
	*/
	public function normalizeValue($value)
	{
		if ($value instanceof AttributeFilter)
		{
			return $value;
		}

		if (is_callable($value))
		{
			return new AttributeFilter($value);
		}

		throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback or an instance of s9e\\TextFormatter\\Configurator\\Items\\AttributeFilter');
	}
}