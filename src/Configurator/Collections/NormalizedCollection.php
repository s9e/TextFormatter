<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;

class NormalizedCollection extends Collection implements ArrayAccess
{
	//==========================================================================
	// Overridable methods
	//==========================================================================

	/**
	* Normalize an item's key
	*
	* This method can be overridden to implement keys normalization or implement constraints
	*
	* @param  string $key Original key
	* @return string      Normalized key
	*/
	public function normalizeKey($key)
	{
		return $key;
	}

	/**
	* Normalize a value for storage
	*
	* This method can be overridden to implement value normalization
	*
	* @param  mixed $value Original value
	* @return mixed        Normalized value
	*/
	public function normalizeValue($value)
	{
		return $value;
	}

	//==========================================================================
	// Items access/manipulation
	//==========================================================================

	/**
	* Add an item to this collection
	*
	* Note: relies on exists() to normalize the key
	*
	* @param  string $key
	* @param  mixed  $value
	* @return mixed
	*/
	public function add($key, $value = null)
	{
		if ($this->exists($key))
		{
			throw new RuntimeException("Item '" . $key . "' already exists");
		}

		return $this->set($key, $value);
	}

	/**
	* Test whether a given value is present in this collection
	*
	* @param  mixed $value
	* @return bool
	*/
	public function contains($value)
	{
		return in_array($this->normalizeValue($value), $this->items);
	}

	/**
	* Delete an item from this collection
	*
	* @param string $key
	*/
	public function delete($key)
	{
		$key = $this->normalizeKey($key);

		unset($this->items[$key]);
	}

	/**
	* Test whether an item of given key exists
	*
	* @param  string $key
	* @return bool
	*/
	public function exists($key)
	{
		$key = $this->normalizeKey($key);

		return array_key_exists($key, $this->items);
	}

	/**
	* Return a value from this collection
	*
	* @param  string $key
	* @return mixed
	*/
	public function get($key)
	{
		if (!$this->exists($key))
		{
			throw new RuntimeException("Item '" . $key . "' does not exist");
		}

		$key = $this->normalizeKey($key);

		return $this->items[$key];
	}

	/**
	* Find the index of a given value
	*
	* Will return the first key associated with the given value, or FALSE if the value is not found
	*
	* @param  mixed $value
	* @return mixed        Index of the value, or FALSE if not found
	*/
	public function indexOf($value)
	{
		return array_search($this->normalizeValue($value), $this->items);
	}

	/**
	* Set and overwrite a value in this collection
	*
	* @param  string $key
	* @param  mixed  $value
	* @return mixed
	*/
	public function set($key, $value)
	{
		$key = $this->normalizeKey($key);

		$this->items[$key] = $this->normalizeValue($value);

		return $this->items[$key];
	}

	//==========================================================================
	// ArrayAccess stuff
	//==========================================================================

	public function offsetExists($offset)
	{
		return $this->exists($offset);
	}

	public function offsetGet($offset)
	{
		return $this->get($offset);
	}

	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}

	public function offsetUnset($offset)
	{
		$this->delete($offset);
	}
}