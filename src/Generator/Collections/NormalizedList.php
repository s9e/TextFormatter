<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator\Collections;

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
	* Test whether a given value is present in this list
	*
	* @param  mixed $value
	* @return bool
	*/
	public function contains($value)
	{
		return in_array($this->normalizeValue($value), $this->items);
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
			array(
				'options' => array(
					'min_range' => 0,
					'max_range' => count($this->items)
				)
			)
		);

		if ($normalizedKey === false)
		{
			throw new InvalidArgumentException("Invalid offset '" . $key . "'");
		}

		return $normalizedKey;
	}
}