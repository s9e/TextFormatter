<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\Items\CallbackTemplate;
use s9e\TextFormatter\ConfigBuilder\Items\Filter;

class FilterChain extends NormalizedList
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
	* @param  mixed  $callback
	* @param  array  $vars
	* @return Filter
	*/
	public function append($callback, array $vars = null)
	{
		return parent::append($this->normalizeFilter($callback, $vars));
	}

	/**
	* Prepend a filter to this chain
	*
	* @param  mixed  $callback
	* @param  array  $vars
	* @return Filter
	*/
	public function prepend($callback, array $vars = null)
	{
		return parent::prepend($this->normalizeFilter($callback, $vars));
	}

	/**
	* Normalize a value argument into a Filter instance
	*
	* @param  mixed  $value
	* @return Filter
	*/
	public function normalizeValue($value)
	{
		if ($value instanceof Filter)
		{
			return $value;
		}

		if ($value instanceof CallbackTemplate)
		{
			$callback = $value;
		}
		elseif (is_string($value) && $value[0] === '#')
		{
			$callback = $value;
		}
		elseif (is_callable($value))
		{
			// It's a callback with no signature, we'll give it the default signature
			$callback = CallbackTemplate::fromArray(array(
				'callback' => $value,
				'params'   => $this->defaultSignature
			));
		}
		else
		{
			throw new InvalidArgumentException("Filter " . var_export($value, true) . " is neither callable or the reference to a built-in filter");
		}

		return new Filter($callback);
	}

	/**
	* Create/normalize a Filter instance based on a callback/filter name and optional vars
	*
	* @param  mixed  $callback
	* @param  array  $vars
	* @return Filter
	*/
	public function normalizeFilter($callback, array $vars = null)
	{
		$filter = $this->normalizeValue($callback);

		if (isset($vars))
		{
			$filter->setVars($vars);
		}

		return $filter;
	}
}