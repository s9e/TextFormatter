<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
use s9e\TextFormatter\ConfigBuilder\Traits\CollectionAccessor;

class NormalizedCollection extends Collection implements ArrayAccess
{
	use CollectionAccessor;

	/**
	* Return a value from this collection
	*
	* @param  string $key
	* @return mixed
	*/
	public function get($key)
	{
		$key = $this->normalizeKey($key);

		if (array_key_exists($key, $this->items))
		{
			throw new RuntimeException("Item '" . $key . "' does not exist");
		}

		return $this->items[$key];
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

	/**
	* Add a value to this collection
	*
	* Note: relies on exists() to validate/normalize the key
	*
	* @param  string $key
	* @param  mixed  $value
	* @return mixed
	*/
	public function add($key, $value)
	{
		if ($this->exists($key))
		{
			throw new RuntimeException("Item '" . $key . "' already exists");
		}

		return $this->set($key, $value);
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

		return isset($this->items[$key]);
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
}