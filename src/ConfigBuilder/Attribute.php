<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use InvalidArgumentException;

class Attribute
{
	/**
	* @param array $options This attribute's options
	*/
	public function __construct(array $options = array())
	{
		$this->setOptions($options);
	}

	/**
	* Return whether a string is a valid attribute name
	*
	* @param  string $name
	* @return bool
	*/
	static public function isValidName($name)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9\\-]*$#Di', $name);
	}

	/**
	* Validate and normalize an attribute name
	*
	* @param  string $name Original attribute name
	* @return string       Normalized attribute name, in lowercase
	*/
	static public function normalizeName($name)
	{
		if (!self::isValidName($name))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $name . "'");
		}

		return strtolower($name);
	}

	//==========================================================================
	// Filters stuff
	//==========================================================================

	/**
	* @param array $filterChain
	*/
	public function setFilterChain(array $filterChain)
	{
		$this->clearFilterChain();

		foreach ($filterChain as $filter)
		{
			$this->appendFilter($filter);
		}
	}

	/**
	* Remove all filters from this attribute's filter chain
	*/
	public function clearFilterChain()
	{
		$this->filterChain = array();
	}

	/**
	* Append a filter to this attribute's filter chain
	*
	* @param  string|callback|Filter $filter
	*/
	public function appendFilter($filter)
	{
		$this->filterChain[] = $this->normalizeFilter($filter);
	}

	/**
	* Prepend a filter to this attribute's filter chain
	*
	* @param  string|callback|Filter $filter
	*/
	public function prependFilter($filter)
	{
		array_unshift($this->filterChain, $this->normalizeFilter($filter));
	}

	/**
	* Normalize a filter definition
	*
	* @param  string|callback|Filter $filter Name of a built-in filter, callback or Filter instance
	* @return string|Filter                  Either a string pointing to a built-in filter, or a
	*                                        Filter object
	*/
	protected function normalizeFilter($filter)
	{
		if ($filter instanceof Filter)
		{
			// Already a Filter object, nothing to do
			return $filter;
		}

		if (is_string($filter) && $filter[0] === '#')
		{
			// It's a built-in filter, return as-is
			return $filter;
		}

		if (is_callable($filter))
		{
			// It's a callback with no signature, we'll assume it just requires the attribute's
			// value
			$filter = new Filter;
			$filter->addParameterByName('attrVal');

			return $filter;
		}

		throw new InvalidArgumentException("Callback '" . var_export($filter, true) . "' is not callable");
	}

	/**
	* Test whether a given filter is present in this attribute's filterChain
	*
	* @param  string|callback|Filter $filter
	* @return bool
	*/
	public function hasFilter($filter)
	{
		return in_array($this->normalizeFilter($filter), $this->filterChain);
	}
}