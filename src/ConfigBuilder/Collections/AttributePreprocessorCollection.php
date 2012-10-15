<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\Items\AttributePreprocessor;
use s9e\TextFormatter\ConfigBuilder\Validators\AttributeName;

class AttributePreprocessorCollection extends Collection
{
	/**
	* Add an attribute preprocessor
	*
	* @param  string $attrName Original name
	* @param  string $regexp   Preprocessor's regexp
	* @return AttributePreprocessor
	*/
	public function add($attrName, $regexp)
	{
		$attrName = AttributeName::normalize($attrName);

		$ap = new AttributePreprocessor($regexp);

		$k = serialize(array($attrName, $regexp));
		$this->items[$k] = $ap;

		return $ap;
	}

	/**
	* {@inheritdoc}
	*/
	public function toConfig()
	{
		$config = array();

		foreach ($this->items as $k => $ap)
		{
			list($attrName, $regexp) = unserialize($k);
			$config[$attrName][] = $regexp;
		}

		return $config;
	}

	/**
	* @return string Name of the attribute the attribute processor uses as source
	*/
	public function key()
	{
		list($attrName, $regexp) = unserialize(key($this->items));

		return $attrName;
	}

	/**
	* Merge a set of attribute preprocessors into this collection
	*
	* @param array|AttributePreprocessorCollection $attributePreprocessors Instance of AttributePreprocessorCollection or 2D array of [attrName=>[regexp/AttributePreprocessor]] 
	*/
	public function merge($attributePreprocessors)
	{
		$error = false;

		if ($attributePreprocessors instanceof AttributePreprocessorCollection)
		{
			foreach ($attributePreprocessors as $attrName => $attributePreprocessor)
			{
				$this->add($attrName, $attributePreprocessor->getRegexp());
			}
		}
		elseif (is_array($attributePreprocessors))
		{
			// This should be an array where keys are attribute names and values should be either
			// an array of regexps and/or AttributePreprocessor instances
			foreach ($attributePreprocessors as $attrName => $values)
			{
				if (!is_array($values))
				{
					$error = true;
					break;
				}

				foreach ($values as $value)
				{
					if ($value instanceof AttributePreprocessor)
					{
						$value = $value->getRegexp();
					}

					$this->add($attrName, $value);
				}
			}
		}
		else
		{
			$error = true;
		}

		if ($error)
		{
			throw new InvalidArgumentException('merge() expects an instance of AttributePreprocessorCollection or a 2D array where keys are attribute names and values are arrays of regexps and AttributePreprocessor instances');
		}
	}
}