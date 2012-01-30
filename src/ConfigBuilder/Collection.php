<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use Iterator,
    ReflectionClass,
    RuntimeException;

class Collection implements Iterator
{
	protected $itemClass;

	/**
	* @param string $itemClass Name of the class of the items this collection will hold
	*/
	public function __construct($itemClass)
	{
		$this->itemClass = $itemClass;
	}

	/**
	* Return a named item from this collection
	*
	* @param  string $name
	* @return Item
	*/
	public function get($name)
	{
		$name = $this->normalizeName($name, true);

		return $this->items[$name];
	}

	/**
	* Add an item to this collection
	*
	* @param  string $name Name for this item
	* @param  mixed  $arg  An Item instance, or any number of arguments passed to the item's
	*                      constructor
	* @return Item         Added item
	*/
	public function add($name, $arg = null)
	{
		$name = $this->normalizeName($name, false);

		$item = ($arg instanceof Item)
		      ? $arg
		      : $this->newItem(array_slice(func_get_args(), 1));

		$this->items[$name] = $arg;

		return $arg;
	}

	/**
	* Remove an item from this collection
	*
	* @param string $name
	*/
	public function remove($name)
	{
		$name = $this->normalizeName($name, true);

		unset($this->items[$name]);
	}

	/**
	* Remove all items from this collection
	*/
	public function clear()
	{
		$this->items = array();
	}

	/**
	* Test whether an item of given name exists
	*
	* @param  string $name
	* @return bool
	*/
	public function exists($name)
	{
		$name = $this->normalizeName($name);

		return isset($this->items[$name]);
	}

	/**
	* Normalize the name of an item
	*
	* @param  string $name      Original name
	* @param  bool   $mustExist Whether the item MUST exist or MUST not exist (can be omitted)
	* @return string            Normalized name
	*/
	protected function normalizeName($name, $mustExist = null)
	{
		$className = $this->itemClass;

		$name = $className::normalizeName($name);

		if (isset($mustExist))
		{
			if ($mustExist)
			{
				if (!isset($this->items[$name]))
				{
					throw new RuntimeException("Item '" . $name . "' does not exist");
				}
			}
			else
			{
				if (isset($this->items[$name]))
				{
					throw new RuntimeException("Item '" . $name . "' already exists");
				}
			}
		}

		return $name;
	}

	/**
	* Create a new Item instance
	*
	* @param  array $args Arguments to be passed to the item's constructor
	* @return Item
	*/
	protected function newItem(array $args)
	{
		$reflection = new ReflectionClass($this->itemClass);

		return $reflection->newInstanceArgs($args);
	}

	//==========================================================================
	// Iterator stuff
	//==========================================================================

	public function rewind()
	{
		reset($this->items);
	}

	public function current()
	{
		return current($this->items);
	}

	function key()
	{
		return key($this->items);
	}

	function next()
	{
		return next($this->items);
	}

	function valid()
	{
		return (key($this->items) !== null);
	}
}