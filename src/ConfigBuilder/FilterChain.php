<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use InvalidArgumentException,
    Iterator;

class FilterChain implements Iterator
{
	/**
	* @var array Default signature, used by Filter instances created from a PHP callback
	*/
	protected $defaultSignature;

	/**
	* @var array This chain's filters
	*/
	protected $filters = array();

	/**
	* Constructor
	*
	* @param array $defaultSignature Default signature used by filters
	*/
	public function __construct(array $defaultSignature)
	{
		$this->defaultSignature = $defaultSignature;
	}

	/**
	* Remove all the filters
	*/
	public function clear()
	{
		$this->filters = array();
	}

	/**
	* Return all the filters
	*/
	public function get()
	{
		return $this->filters;
	}

	/**
	* Append a filter to this chain
	*
	* @param  string|callback|Filter $filter
	*/
	public function append($filter)
	{
		$this->filters[] = $this->normalizeFilter($filter);
	}

	/**
	* Prepend a filter to this chain
	*
	* @param  string|callback|Filter $filter
	*/
	public function prepend($filter)
	{
		array_unshift($this->filters, $this->normalizeFilter($filter));
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
			// It's a callback with no signature, we'll give it the default signature
			return Filter::fromArray(array(
				'callback' => $filter,
				'params'   => $this->defaultSignature
			));
		}

		throw new InvalidArgumentException("Callback '" . var_export($filter, true) . "' is not callable");
	}

	/**
	* Test whether a given filter is present in this chain
	*
	* @param  string|callback|Filter $filter
	* @return bool
	*/
	public function has($filter)
	{
		return in_array($this->normalizeFilter($filter), $this->filters);
	}

	//==========================================================================
	// Iterator stuff
	//==========================================================================

	public function rewind()
	{
		reset($this->filters);
	}

	public function current()
	{
		return current($this->filters);
	}

	function key()
	{
		return key($this->filters);
	}

	function next()
	{
		return next($this->filters);
	}

	function valid()
	{
		return (key($this->filters) !== null);
	}
}