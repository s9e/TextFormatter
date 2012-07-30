<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\Items\Filter;
use s9e\TextFormatter\ConfigBuilder\Items\FilterLink;

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
	* @param string|callback|Filter|FilterLink
	* @param array
	*/
	public function append()
	{
		$this->items[] = $this->normalizeLink(func_get_args());
	}

	/**
	* Prepend a filter to this chain
	*
	* @param string|callback|Filter|FilterLink
	* @param array
	*/
	public function prepend()
	{
		array_unshift($this->items, $this->normalizeLink(func_get_args()));
	}

	/**
	* Normalize a link in the filterChain
	*
	* @param  string|callback|Filter|FilterLink $filter
	* @param  array                             $filterConfig
	* @return FilterLink
	*/
	protected function normalizeLink(array $args)
	{
		if ($args[0] instanceof FilterLink)
		{
			return $args[0];
		}

		if ($args[0] instanceof Filter)
		{
			$filter = $args[0];
		}
		elseif (is_string($args[0]) && $args[0][0] === '#')
		{
			$filter = $args[0];
		}
		elseif (is_callable($args[0]))
		{
			// It's a callback with no signature, we'll give it the default signature
			$filter = Filter::fromArray(array(
				'callback' => $args[0],
				'params'   => $this->defaultSignature
			));
		}
		else
		{
			throw new InvalidArgumentException("Filter " . var_export($args[0], true) . " is not callable");
		}

		$filterConfig = (isset($args[1])) ? $args[1] : array();

		return new FilterLink($filter, $filterConfig);
	}

	/**
	* Test whether a given filter is present in this chain
	*
	* @param  string|callback|Filter|FilterLink
	* @param  array
	* @return bool
	*/
	public function has()
	{
		return in_array($this->normalizeLink(func_get_args()), $this->items);
	}
}