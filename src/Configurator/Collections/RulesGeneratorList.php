<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\BooleanRulesGenerator;
use s9e\TextFormatter\Configurator\RulesGenerators\Interfaces\TargetedRulesGenerator;

class RulesGeneratorList extends NormalizedList
{
	/**
	* Normalize the value to an object
	*
	* @param  string|BooleanRulesGenerator|TargetedRulesGenerator $generator Either a string, or an instance of a rules generator
	* @return BooleanRulesGenerator|TargetedRulesGenerator
	*/
	public function normalizeValue($generator)
	{
		if (is_string($generator))
		{
			$className = 's9e\\TextFormatter\\Configurator\\RulesGenerators\\' . $generator;

			if (class_exists($className))
			{
				$generator = new $className;
			}
		}

		if (!($generator instanceof BooleanRulesGenerator)
		 && !($generator instanceof TargetedRulesGenerator))
		{
			throw new InvalidArgumentException('Invalid rules generator ' . var_export($generator, true));
		}

		return $generator;
	}

	/**
	* {@inheritdoc}
	*/
	public function remove($value)
	{
		if (is_string($value))
		{
			// Select by class name to avoid costly object instantiations
			$className = get_class($this->normalizeValue($value));

			$cnt = 0;
			foreach ($this->items as $i => $rulesGenerator)
			{
				if ($rulesGenerator instanceof $className)
				{
					++$cnt;
					unset($this->items[$i]);
				}
			}
			$this->items = array_values($this->items);

			return $cnt;
		}

		return parent::remove($value);
	}
}