<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator\Traits;

use InvalidArgumentException;

/**
* Allows an object to act as a proxy for a NormalizedCollection stored in $this->collection
*/
trait CollectionProxy
{
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

	/**
	* Forwarding method to $this->collection->add()
	*/
	public function add()
	{
		return call_user_func_array(array($this->collection, 'add'), func_get_args());
	}

	/**
	* Forwarding method to $this->collection->delete()
	*/
	public function delete()
	{
		return call_user_func_array(array($this->collection, 'delete'), func_get_args());
	}

	/**
	* Forwarding method to $this->collection->exists()
	*/
	public function exists()
	{
		return call_user_func_array(array($this->collection, 'exists'), func_get_args());
	}

	/**
	* Forwarding method to $this->collection->get()
	*/
	public function get()
	{
		return call_user_func_array(array($this->collection, 'get'), func_get_args());
	}

	/**
	* Forwarding method to $this->collection->set()
	*/
	public function set()
	{
		return call_user_func_array(array($this->collection, 'set'), func_get_args());
	}
}