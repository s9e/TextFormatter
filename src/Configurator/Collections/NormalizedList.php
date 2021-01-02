<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;

class NormalizedList extends NormalizedCollection
{
	/**
	* Add (append) a value to this list
	*
	* Alias for append(). Overrides NormalizedCollection::add()
	*
	* @param  mixed $value Original value
	* @param  null  $void  Unused
	* @return mixed        Normalized value
	*/
	public function add($value, $void = null)
	{
		return $this->append($value);
	}

	/**
	* Append a value to this list
	*
	* @param  mixed $value Original value
	* @return mixed        Normalized value
	*/
	public function append($value)
	{
		$value = $this->normalizeValue($value);

		$this->items[] = $value;

		return $value;
	}

	/**
	* Delete a value from this list and remove gaps in keys
	*
	* NOTE: parent::offsetUnset() maps to $this->delete() so this method covers both usages
	*
	* @param  string $key
	* @return void
	*/
	public function delete($key)
	{
		parent::delete($key);

		// Reindex the array to eliminate any gaps
		$this->items = array_values($this->items);
	}

	/**
	* Insert a value at an arbitrary 0-based position
	*
	* @param  integer $offset
	* @param  mixed   $value
	* @return mixed           Normalized value
	*/
	public function insert($offset, $value)
	{
		$offset = $this->normalizeKey($offset);
		$value  = $this->normalizeValue($value);

		// Insert the value at given offset. We put the value into an array so that array_splice()
		// won't insert it as multiple elements if it happens to be an array
		array_splice($this->items, $offset, 0, [$value]);

		return $value;
	}

	/**
	* Ensure that the key is a valid offset
	*
	* Negative values count from the end of the list
	*
	* @param  mixed   $key
	* @return integer
	*/
	public function normalizeKey($key)
	{
		$normalizedKey = filter_var(
			(preg_match('(^-\\d+$)D', $key)) ? count($this->items) + $key : $key,
			FILTER_VALIDATE_INT,
			[
				'options' => [
					'min_range' => 0,
					'max_range' => count($this->items)
				]
			]
		);

		if ($normalizedKey === false)
		{
			throw new InvalidArgumentException("Invalid offset '" . $key . "'");
		}

		return $normalizedKey;
	}

	/**
	* Custom offsetSet() implementation to allow assignment with a null offset to append to the
	* chain
	*
	* @param  mixed $offset
	* @param  mixed $value
	* @return void
	*/
	public function offsetSet($offset, $value)
	{
		if ($offset === null)
		{
			// $list[] = 'foo' maps to $list->append('foo')
			$this->append($value);
		}
		else
		{
			// Use the default implementation
			parent::offsetSet($offset, $value);
		}
	}

	/**
	* Prepend a value to this list
	*
	* @param  mixed $value
	* @return mixed        Normalized value
	*/
	public function prepend($value)
	{
		$value = $this->normalizeValue($value);

		array_unshift($this->items, $value);

		return $value;
	}

	/**
	* Remove all items matching given value
	*
	* @param  mixed   $value Original value
	* @return integer        Number of items removed
	*/
	public function remove($value)
	{
		$keys = array_keys($this->items, $this->normalizeValue($value));
		foreach ($keys as $k)
		{
			unset($this->items[$k]);
		}

		$this->items = array_values($this->items);

		return count($keys);
	}
}