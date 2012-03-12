<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException,
    Iterator,
    s9e\TextFormatter\ConfigBuilder\Items\Filter;

class FilterChain extends Collection
{
	/**
	* @var array Default signature, used by Filter instances created from a PHP callback
	*/
	protected $defaultSignature;

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
	* Append a filter to this chain
	*
	* @param  string|callback|Filter $filter
	*/
	public function append($filter)
	{
		$this->items[] = $this->normalizeFilter($filter);
	}

	/**
	* Prepend a filter to this chain
	*
	* @param  string|callback|Filter $filter
	*/
	public function prepend($filter)
	{
		array_unshift($this->items, $this->normalizeFilter($filter));
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
		return in_array($this->normalizeFilter($filter), $this->items);
	}
}