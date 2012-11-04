<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\CallbackPlaceholder;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;

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
	* @param  mixed                $callback
	* @param  array                $vars
	* @return ProgrammableCallback
	*/
	public function append($callback, array $vars = null)
	{
		return parent::append($this->normalizeFilter($callback, $vars));
	}

	/**
	* Prepend a filter to this chain
	*
	* @param  mixed                $callback
	* @param  array                $vars
	* @return ProgrammableCallback
	*/
	public function prepend($callback, array $vars = null)
	{
		return parent::prepend($this->normalizeFilter($callback, $vars));
	}

	/**
	* Normalize a value argument into a ProgrammableCallback instance
	*
	* @param  mixed                $value Either a callback or the name of a built-in filter
	* @return ProgrammableCallback
	*/
	public function normalizeValue($value)
	{
		if ($value instanceof ProgrammableCallback)
		{
			return $value;
		}

		if (is_string($value) && $value[0] === '#')
		{
			$value = new CallbackPlaceholder($value);
		}
		elseif (!is_callable($value))
		{
			throw new InvalidArgumentException('Filter ' . var_export($value, true) . ' is neither callable nor the name of a filter');
		}

		return new ProgrammableCallback($value);
	}

	/**
	* Create/normalize a ProgrammableCallback instance based on a callback/filter name and optional
	* vars
	*
	* @param  mixed                $callback
	* @param  array                $vars
	* @return ProgrammableCallback
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