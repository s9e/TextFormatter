<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\Configurator\Items\AttributePreprocessor;
use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegexpConvertor;
use s9e\TextFormatter\Configurator\Validators\AttributeName;

class AttributePreprocessorCollection extends Collection
{
	public function add($attrName, $regexp)
	{
		$attrName = AttributeName::normalize($attrName);

		$k = \serialize([$attrName, $regexp]);
		$this->items[$k] = new AttributePreprocessor($regexp);

		return $this->items[$k];
	}

	public function key()
	{
		list($attrName) = \unserialize(\key($this->items));

		return $attrName;
	}

	public function merge($attributePreprocessors)
	{
		$error = \false;

		if ($attributePreprocessors instanceof AttributePreprocessorCollection)
			foreach ($attributePreprocessors as $attrName => $attributePreprocessor)
				$this->add($attrName, $attributePreprocessor->getRegexp());
		elseif (\is_array($attributePreprocessors))
		{
			foreach ($attributePreprocessors as $values)
			{
				if (!\is_array($values))
				{
					$error = \true;
					break;
				}

				list($attrName, $value) = $values;

				if ($value instanceof AttributePreprocessor)
					$value = $value->getRegexp();

				$this->add($attrName, $value);
			}
		}
		else
			$error = \true;

		if ($error)
			throw new InvalidArgumentException('merge() expects an instance of AttributePreprocessorCollection or a 2D array where each element is a [attribute name, regexp] pair');
	}

	public function asConfig()
	{
		$config = [];

		foreach ($this->items as $k => $ap)
		{
			list($attrName, $regexp) = \unserialize($k);

			$jsRegexp = RegexpConvertor::toJS($regexp);

			$config[] = new Variant(
				[$attrName, $regexp],
				[
					'JS' => [$attrName, $jsRegexp, $jsRegexp->map]
				]
			);
		}

		return $config;
	}
}