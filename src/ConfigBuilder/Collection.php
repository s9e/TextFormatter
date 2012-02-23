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

abstract class Collection implements ConfigProvider, Iterator
{
	/**
	* @var array Items that this collection holds
	*/
	protected $items = array();

	//==========================================================================
	// Abstract methods
	//==========================================================================

	/**
	* Return the class name of this collection's items
	*
	* @return string
	*/
	abstract protected function getItemClass();

	//==========================================================================
	// Common methods
	//==========================================================================

	/**
	* Return whether a string would be a valid item name
	*
	* @param  string $name
	* @return bool
	*/
	public function isValidName($name)
	{
		return true;
	}

	/**
	* Validate and normalize an item's name
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	public function normalizeName($name)
	{
		return $name;
	}

	public function getConfig()
	{
		$config = array();

		foreach ($this->items as $name => $item)
		{
			$config[$name] = $item->getConfig();
		}

		return $config;
	}

	/**
	* Return a named item from this collection
	*
	* @param  string $name
	* @return Item
	*/
	public function get($name)
	{
		$name = $this->validateName($name, true);

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
		$name = $this->validateName($name, false);

		$class = $this->getItemClass();

		if ($arg instanceof $class)
		{
			$item = $arg;
		}
		else
		{
			$reflection = new ReflectionClass($class);
			$item = $reflection->newInstanceArgs(array_slice(func_get_args(), 1));
		}

		$this->items[$name] = $item;

		return $item;
	}

	/**
	* Remove an item from this collection
	*
	* @param string $name
	*/
	public function remove($name)
	{
		$name = $this->validateName($name, true);

		unset($this->items[$name]);
	}

	/**
	* Rename an item in this collection
	*
	* @param string $oldName
	* @param string $newName
	*/
	public function rename($oldName, $newName)
	{
		$oldName = $this->validateName($oldName, true);
		$newName = $this->validateName($newName, false);

		$this->items[$newName] = $this->items[$oldName];
		unset($this->items[$oldName]);
	}

	/**
	* Test whether an item of given name exists
	*
	* @param  string $name
	* @return bool
	*/
	public function exists($name)
	{
		$name = $this->validateName($name);

		return isset($this->items[$name]);
	}

	/**
	* Remove all items from this collection
	*/
	public function clear()
	{
		$this->items = array();
	}

	/**
	* Validate and normalize the name of an item
	*
	* @param  string $name      Original name
	* @param  bool   $mustExist Whether the item MUST exist or MUST NOT exist (can be omitted)
	* @return string            Normalized name
	*/
	protected function validateName($name, $mustExist = null)
	{
		if (!$this->isValidName($name))
		{
			throw new InvalidArgumentException ("Invalid item name '" . $name . "'");
		}

		$name = $this->normalizeName($name);

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