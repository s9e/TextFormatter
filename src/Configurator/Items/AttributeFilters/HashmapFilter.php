<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items\AttributeFilters;

use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\ContextSafeness;
use s9e\TextFormatter\Configurator\Items\AttributeFilter;
use s9e\TextFormatter\Configurator\JavaScript\Dictionary;

class HashmapFilter extends AttributeFilter
{
	/**
	* Constructor
	*
	* @param  array $map    Associative array in the form [key => value]
	* @param  bool  $strict Whether this map is strict (values with no match are invalid)
	*/
	public function __construct(array $map = null, $strict = false)
	{
		parent::__construct('s9e\\TextFormatter\\Parser\\AttributeFilters\\HashmapFilter::filter');

		$this->resetParameters();
		$this->addParameterByName('attrValue');
		$this->addParameterByName('map');
		$this->addParameterByName('strict');
		$this->setJS('HashmapFilter.filter');

		if (isset($map))
		{
			$this->setMap($map, $strict);
		}
	}

	/**
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		if (!isset($this->vars['map']))
		{
			throw new RuntimeException("Hashmap filter is missing a 'map' value");
		}

		return parent::asConfig();
	}

	/**
	* Set the content of this map
	*
	* @param  array $map    Associative array in the form [word => replacement]
	* @param  bool  $strict Whether this map is strict (values with no match are invalid)
	* @return void
	*/
	public function setMap(array $map, $strict = false)
	{
		if (!is_bool($strict))
		{
			throw new InvalidArgumentException('Argument 2 passed to ' . __METHOD__ . ' must be a boolean');
		}

		// If the map is not strict, we can optimize away the values that are identical to their key
		if (!$strict)
		{
			$map = $this->optimizeLooseMap($map);
		}

		// Sort the map so it looks tidy
		ksort($map);

		// Record this filter's variables
		$this->vars['map']    = new Dictionary($map);
		$this->vars['strict'] = $strict;

		// Evaluate safeness
		$this->resetSafeness();
		if (!empty($this->vars['strict']))
		{
			$this->evaluateSafenessInCSS();
			$this->evaluateSafenessInJS();
		}
	}

	/**
	* Mark whether this filter makes a value safe to be used in CSS
	*
	* @return void
	*/
	protected function evaluateSafenessInCSS()
	{
		// Test each value against the list of disallowed characters
		$disallowedChars = ContextSafeness::getDisallowedCharactersInCSS();
		foreach ($this->vars['map'] as $value)
		{
			foreach ($disallowedChars as $char)
			{
				if (strpos($value, $char) !== false)
				{
					return;
				}
			}
		}

		$this->markAsSafeInCSS();
	}

	/**
	* Mark whether this filter makes a value safe to be used in JS
	*
	* @return void
	*/
	protected function evaluateSafenessInJS()
	{
		// Test each value against the list of disallowed characters
		$disallowedChars = ContextSafeness::getDisallowedCharactersInJS();
		foreach ($this->vars['map'] as $value)
		{
			foreach ($disallowedChars as $char)
			{
				if (strpos($value, $char) !== false)
				{
					return;
				}
			}
		}

		$this->markAsSafeInJS();
	}

	/**
	* Optimize a non-strict map by removing values that are identical to their key
	*
	* @param  array $map Original map
	* @return array      Optimized map
	*/
	protected function optimizeLooseMap(array $map)
	{
		foreach ($map as $k => $v)
		{
			if ($k === $v)
			{
				unset($map[$k]);
			}
		}

		return $map;
	}
}