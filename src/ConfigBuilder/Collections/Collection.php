<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use Countable;
use Iterator;
use s9e\TextFormatter\ConfigBuilder\ConfigProvider;
use s9e\TextFormatter\ConfigBuilder\Helpers\ConfigHelper;

class Collection implements ConfigProvider, Countable, Iterator
{
	/**
	* @var array Items that this collection holds
	*/
	protected $items = array();

	/**
	* Empty this collection
	*/
	public function clear()
	{
		$this->items = array();
	}

	/**
	* {@inheritdoc}
	*/
	public function toConfig()
	{
		return ConfigHelper::toArray($this->items);
	}

	//==========================================================================
	// Countable stuff
	//==========================================================================

	public function count()
	{
		return count($this->items);
	}

	//==========================================================================
	// Iterator stuff
	//==========================================================================

	public function current()
	{
		return current($this->items);
	}

	public function key()
	{
		return key($this->items);
	}

	public function next()
	{
		return next($this->items);
	}

	public function rewind()
	{
		reset($this->items);
	}

	public function valid()
	{
		return (key($this->items) !== null);
	}
}