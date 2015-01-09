<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

trait CollectionProxy
{
	public function __call($methodName, $args)
	{
		return \call_user_func_array([$this->collection, $methodName], $args);
	}

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

	public function count()
	{
		return \count($this->collection);
	}

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