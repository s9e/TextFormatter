<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

use InvalidArgumentException;

/**
* Allows an object to act as a proxy for a NormalizedCollection stored in $this->collection
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
		return call_user_func_array(array($this->collection, $methodName), $args);
	}

	//==========================================================================
	// ArrayAccess
	//==========================================================================

	public function offsetExists($offset)
	{
		return isset($this->collection[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->collection[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->collection[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->collection[$offset]);
	}

	//==========================================================================
	// Countable
	//==========================================================================

	public function count()
	{
		return count($this->collection);
	}

	//==========================================================================
	// Iterator
	//==========================================================================

	public function current()
	{
		return $this->collection->current();
	}

	public function key()
	{
		return $this->collection->key();
	}

	public function next()
	{
		return $this->collection->next();
	}

	public function rewind()
	{
		$this->collection->rewind();
	}

	public function valid()
	{
		return $this->collection->valid();
	}
}