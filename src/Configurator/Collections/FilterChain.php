<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Helpers\FilterHelper;
use s9e\TextFormatter\Configurator\Items\Filter;
use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;
abstract class FilterChain extends NormalizedList
{
	abstract protected function getFilterClassName();
	public function containsCallback($callback)
	{
		$pc = new ProgrammableCallback($callback);
		$callback = $pc->getCallback();
		foreach ($this->items as $filter)
			if ($callback === $filter->getCallback())
				return \true;
		return \false;
	}
	public function normalizeValue($value)
	{
		if (\is_string($value) && \strpos($value, '(') !== \false)
			return $this->createFilter($value);
		$className = $this->getFilterClassName();
		if ($value instanceof $className)
			return $value;
		if (!\is_callable($value))
			throw new InvalidArgumentException('Filter ' . \var_export($value, \true) . ' is neither callable nor an instance of ' . $className);
		return new $className($value);
	}
	protected function createFilter($filterString)
	{
		$config = FilterHelper::parse($filterString);
		$filter = $this->normalizeValue($config['filter']);
		if (isset($config['params']))
		{
			$filter->resetParameters();
			foreach ($config['params'] as array($type, $value))
			{
				$methodName = 'addParameterBy' . $type;
				$filter->$methodName($value);
			}
		}
		return $filter;
	}
}