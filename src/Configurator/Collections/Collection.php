<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use Countable;
use Iterator;
use s9e\TextFormatter\Configurator\ConfigProvider;
use s9e\TextFormatter\Configurator\Helpers\ConfigHelper;
class Collection implements ConfigProvider, Countable, Iterator
{
	protected $items = array();
	public function clear()
	{
		$this->items = array();
	}
	public function asConfig()
	{
		return ConfigHelper::toArray($this->items, \true);
	}
	public function count()
	{
		return \count($this->items);
	}
	public function current()
	{
		return \current($this->items);
	}
	public function key()
	{
		return \key($this->items);
	}
	public function next()
	{
		return \next($this->items);
	}
	public function rewind()
	{
		\reset($this->items);
	}
	public function valid()
	{
		return (\key($this->items) !== \null);
	}
}