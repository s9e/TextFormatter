<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;

class Variant
{
	protected $defaultValue;

	protected $variants = [];

	public function __construct($value = \null, array $variants = [])
	{
		if ($value instanceof self)
		{
			$this->defaultValue = $value->defaultValue;
			$this->variants     = $value->variants;
		}
		else
			$this->defaultValue = $value;

		foreach ($variants as $k => $v)
			$this->set($k, $v);
	}

	public function __toString()
	{
		return (string) $this->defaultValue;
	}

	public function get($variant = \null)
	{
		if (isset($variant) && isset($this->variants[$variant]))
		{
			list($isDynamic, $value) = $this->variants[$variant];

			return ($isDynamic) ? $value() : $value;
		}

		return $this->defaultValue;
	}

	public function has($variant)
	{
		return isset($this->variants[$variant]);
	}

	public function set($variant, $value)
	{
		$this->variants[$variant] = [\false, $value];
	}

	public function setDynamic($variant, $callback)
	{
		if (!\is_callable($callback))
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a valid callback');

		$this->variants[$variant] = [\true, $callback];
	}
}