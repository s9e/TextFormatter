<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;

class NormalizedList extends NormalizedCollection
{
	/**
	* Append a value to this list
	*
	* @param  mixed $value
	* @return mixed        Normalized value
	*/
	public function append($value)
	{
		$value = $this->normalizeValue($value);

		$this->items[] = $value;

		return $value;
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
	* Ensure that the key is a valid offset, ranging from 0 to count($this->items)
	*
	* @param  mixed   $key
	* @return integer
	*/
	public function normalizeKey($key)
	{
		$normalizedKey = filter_var(
			$key,
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
}