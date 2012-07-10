<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Items\AttributePreprocessor;

class AttributePreprocessorCollection
{
	/**
	* Normalize an attribute preprocessor's name
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	public function normalizeName($name)
	{
		return strtolower($name);
	}

	/**
	* Add an attribute preprocessor
	*
	* @param  string $attrName Original name
	* @param  string $regexp   Preprocessor's regexp
	* @return AttributePreprocessor
	*/
	public function add($attrName, $regexp)
	{
		$ap = new AttributePreprocessor($regexp);

		$attrName = $this->normalizeName($attrName);
		$this->items[$attrName][$regexp] = $ap;

		return $ap;
	}

	/**
	* Return the regexps associated with each attribute
	*
	* @return array
	*/
	public function getConfig()
	{
		return array_map('array_keys', $this->items);
	}
}