<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Traits;

use InvalidArgumentException;

trait CollectionAccessor
{
	public function offsetExists($offset)
	{
		return isset($this->items[$offset]);
	}

	public function offsetGet($offset)
	{
		return $this->items[$offset];
	}

	public function offsetSet($offset, $value)
	{
		$this->items[$offset] = $value;
	}

	public function offsetUnset($offset)
	{
		unset($this->items[$offset]);
	}
}