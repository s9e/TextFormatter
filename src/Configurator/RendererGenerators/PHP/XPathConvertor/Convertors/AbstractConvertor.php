<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

use s9e\TextFormatter\Configurator\RecursiveParser\AbstractRecursiveMatcher;

abstract class AbstractConvertor extends AbstractRecursiveMatcher
{
	/**
	* Retrieve the attribute name from an attribute expression
	*
	* @param  string $expr XPath expression for an attribute, e.g. '@foo'
	* @return string       Attribute name, e.g. 'foo'
	*/
	protected function getAttributeName($expr)
	{
		return preg_replace('([\\s@])', '', $expr);
	}

	/**
	* Normalize a number representation
	*
	* @param  string $sign
	* @param  string $number
	* @return string
	*/
	protected function normalizeNumber($sign, $number)
	{
		// Remove leading zeros and normalize -0 to 0
		$number = ltrim($number, '0');

		return ($number === '') ? '0' : $sign . $number;
	}
}