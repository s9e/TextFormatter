<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

/**
* Allows an object to act as a proxy for a NormalizedCollection stored in $this->collection
*
* @property \s9e\TextFormatter\Collections\NormalizedCollection $collection
*
* @method mixed   add(string $key, mixed $value)
* @method array   asConfig()
* @method bool    contains(mixed $value)
* @method void    delete(string $key)
* @method bool    exists(string $key)
* @method mixed   get(string $key)
* @method mixed   indexOf(mixed $value)
* @method string  normalizeKey(string $key)
* @method mixed   normalizeValue(mixed $value)
* @method string  onDuplicate(string $action)
* @method mixed   set(string $key, mixed $value)
*/
trait CollectionProxy
{
	/**
	* Forward all unknown method calls to $this->collection
	*
	* @param  string $methodName
	* @param  array  $args
	* @return mixed
	*/
	public function __call($methodName, $args)
	{
		return call_user_func_array([$this->collection, $methodName], $args);
	}

	//==========================================================================
	// ArrayAccess
	//==========================================================================

	/**
	* @param  string|integer $offset
	* @return bool
	*/
	public function offsetExists($offset): bool
	{
		return isset($this->collection[$offset]);
	}

	/**
	* @param  string|integer $offset
	* @return mixed
	*/
	#[\ReturnTypeWillChange]
	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}

	/**
	* @param  string|integer $offset
	* @param  mixed          $value
	* @return void
	*/
	public function offsetSet($offset, $value): void
	{
		$this->collection[$offset] = $value;
	}

	/**
	* @param  string|integer $offset
	* @return void
	*/
	public function offsetUnset($offset): void
	{
		unset($this->collection[$offset]);
	}

	//==========================================================================
	// Countable
	//==========================================================================

	/**
	* @return integer
	*/
	public function count(): int
	{
		return count($this->collection);
	}

	//==========================================================================
	// Iterator
	//==========================================================================

	/**
	* @return mixed
	*/
	#[\ReturnTypeWillChange]
	public function current()
	{
		return $this->collection->current();
	}

	/**
	* @return string|integer
	*/
	#[\ReturnTypeWillChange]
	public function key()
	{
		return $this->collection->key();
	}

	/**
	* @return mixed
	*/
	#[\ReturnTypeWillChange]
	public function next()
	{
		return $this->collection->next();
	}

	/**
	* @return void
	*/
	public function rewind(): void
	{
		$this->collection->rewind();
	}

	/**
	* @return boolean
	*/
	public function valid(): bool
	{
		return $this->collection->valid();
	}
}