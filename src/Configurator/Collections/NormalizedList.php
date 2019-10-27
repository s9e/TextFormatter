<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
class NormalizedList extends NormalizedCollection
{
	public function add($value, $void = \null)
	{
		return $this->append($value);
	}
	public function append($value)
	{
		$value = $this->normalizeValue($value);
		$this->items[] = $value;
		return $value;
	}
	public function delete($key)
	{
		parent::delete($key);
		$this->items = \array_values($this->items);
	}
	public function insert($offset, $value)
	{
		$offset = $this->normalizeKey($offset);
		$value  = $this->normalizeValue($value);
		\array_splice($this->items, $offset, 0, array($value));
		return $value;
	}
	public function normalizeKey($key)
	{
		$normalizedKey = \filter_var(
			(\preg_match('(^-\\d+$)D', $key)) ? \count($this->items) + $key : $key,
			\FILTER_VALIDATE_INT,
			array(
				'options' => array(
					'min_range' => 0,
					'max_range' => \count($this->items)
				)
			)
		);
		if ($normalizedKey === \false)
			throw new InvalidArgumentException("Invalid offset '" . $key . "'");
		return $normalizedKey;
	}
	public function offsetSet($offset, $value)
	{
		if ($offset === \null)
			$this->append($value);
		else
			parent::offsetSet($offset, $value);
	}
	public function prepend($value)
	{
		$value = $this->normalizeValue($value);
		\array_unshift($this->items, $value);
		return $value;
	}
	public function remove($value)
	{
		$keys = \array_keys($this->items, $this->normalizeValue($value));
		foreach ($keys as $k)
			unset($this->items[$k]);
		$this->items = \array_values($this->items);
		return \count($keys);
	}
}