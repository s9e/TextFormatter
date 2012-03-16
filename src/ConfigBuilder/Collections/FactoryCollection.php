<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException,
    ReflectionClass,
    RuntimeException;

abstract class FactoryCollection extends Collection
{
	//==========================================================================
	// Abstract methods
	//==========================================================================

	/**
	* Return the class name of this collection's items
	*
	* @return string
	*/
	abstract protected function getItemClass();

	/**
	* Return whether a string would be a valid item name
	*
	* @param  string $name
	* @return bool
	*/
	abstract public function isValidName($name);

	/**
	* Validate and normalize an item's name
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	abstract public function normalizeName($name);

	//==========================================================================
	// Common methods
	//==========================================================================

	/**
	* Return a named item from this collection
	*
	* @param  string $name
	* @return object
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
	* @param  mixed  $arg  An Item instance, or any number of arguments passed to the item's ctor
	* @return object       Added item
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
}