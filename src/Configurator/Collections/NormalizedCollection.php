<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use ArrayAccess;
use InvalidArgumentException;
use RuntimeException;
class NormalizedCollection extends Collection implements ArrayAccess
{
	protected $onDuplicateAction = 'error';
	public function asConfig()
	{
		$config = parent::asConfig();
		\ksort($config);
		return $config;
	}
	public function onDuplicate($action = \null)
	{
		$old = $this->onDuplicateAction;
		if (\func_num_args() && $action !== 'error' && $action !== 'ignore' && $action !== 'replace')
			throw new InvalidArgumentException("Invalid onDuplicate action '" . $action . "'. Expected: 'error', 'ignore' or 'replace'");
		$this->onDuplicateAction = $action;
		return $old;
	}
	protected function getAlreadyExistsException($key)
	{
		return new RuntimeException("Item '" . $key . "' already exists");
	}
	protected function getNotExistException($key)
	{
		return new RuntimeException("Item '" . $key . "' does not exist");
	}
	public function normalizeKey($key)
	{
		return $key;
	}
	public function normalizeValue($value)
	{
		return $value;
	}
	public function add($key, $value = \null)
	{
		if ($this->exists($key))
			if ($this->onDuplicateAction === 'ignore')
				return $this->get($key);
			elseif ($this->onDuplicateAction === 'error')
				throw $this->getAlreadyExistsException($key);
		return $this->set($key, $value);
	}
	public function contains($value)
	{
		return \in_array($this->normalizeValue($value), $this->items);
	}
	public function delete($key)
	{
		$key = $this->normalizeKey($key);
		unset($this->items[$key]);
	}
	public function exists($key)
	{
		$key = $this->normalizeKey($key);
		return \array_key_exists($key, $this->items);
	}
	public function get($key)
	{
		if (!$this->exists($key))
			throw $this->getNotExistException($key);
		$key = $this->normalizeKey($key);
		return $this->items[$key];
	}
	public function indexOf($value)
	{
		return \array_search($this->normalizeValue($value), $this->items);
	}
	public function set($key, $value)
	{
		$key = $this->normalizeKey($key);
		$this->items[$key] = $this->normalizeValue($value);
		return $this->items[$key];
	}
	public function offsetExists($offset)
	{
		return $this->exists($offset);
	}
	public function offsetGet($offset)
	{
		return $this->get($offset);
	}
	public function offsetSet($offset, $value)
	{
		$this->set($offset, $value);
	}
	public function offsetUnset($offset)
	{
		$this->delete($offset);
	}
}