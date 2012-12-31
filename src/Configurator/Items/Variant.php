<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

class Variant
{
	/**
	* @var mixed Default value
	*/
	protected $defaultValue;

	/**
	* @var array Variants
	*/
	protected $variants = array();

	/**
	* Constructor
	*
	* @param  mixed $value Default value
	* @return void
	*/
	public function __construct($value)
	{
		// If we're trying to create a variant of a variant, we just become a copy of it
		if ($value instanceof self)
		{
			$this->defaultValue = $value->defaultValue;
			$this->variants     = $value->variants;
		}
		else
		{
			$this->defaultValue = $value;
		}
	}

	/**
	* Get this value, either from preferred variant or the default value
	*
	* @param  string $variant Preferred variant
	* @return mixed           Value from preferred variant if available, default value otherwise
	*/
	public function get($variant = null)
	{
		if (isset($variant) && isset($this->variants[$variant]))
		{
			return $this->variants[$variant];
		}

		return $this->defaultValue;
	}

	/**
	* Return whether a value exists for given variant
	*
	* @param  string $variant Variant name
	* @return bool            Whether given variant exists
	*/
	public function has($variant)
	{
		return isset($this->variants[$variant]);
	}

	/**
	* Set a variant for this value
	*
	* @param  string $variant Name of variant
	* @param  mixed  $value   Variant's value
	* @return void
	*/
	public function set($variant, $value)
	{
		$this->variants[$variant] = $value;
	}
}