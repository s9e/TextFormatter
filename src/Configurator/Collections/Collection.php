<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;

class Collection implements ConfigProvider, Countable, Iterator
{
	/**
	* @var array Items that this collection holds
	*/
	protected $items = [];

	/**
	* Empty this collection
	*/
	public function clear()
	{
		$this->items = [];
	}

	/**
	* @return mixed
	*/
	public function asConfig()
	{
		return ConfigHelper::toArray($this->items, true);
	}

	//==========================================================================
	// Countable stuff
	//==========================================================================

	/**
	* @return integer
	*/
	public function count()
	{
		return count($this->items);
	}

	//==========================================================================
	// Iterator stuff
	//==========================================================================

	/**
	* @return mixed
	*/
	public function current()
	{
		return current($this->items);
	}

	/**
	* @return integer|string
	*/
	public function key()
	{
		return key($this->items);
	}

	/**
	* @return mixed
	*/
	public function next()
	{
		return next($this->items);
	}

	/**
	* @return void
	*/
	public function rewind()
	{
		reset($this->items);
	}

	/**
	* @return bool
	*/
	public function valid()
	{
		return (key($this->items) !== null);
	}
}