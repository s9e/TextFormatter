<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

use s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Runner;

abstract class AbstractConvertor
{
	/**
	* @var Runner
	*/
	protected $runner;

	/**
	* Constructor
	*
	* @param  Runner $runner
	* @return void
	*/
	public function __construct(Runner $runner)
	{
		$this->runner = $runner;
	}

	/**
	* Return the name of the group each regexp belongs to
	*
	* @return array Regexp's name as key, regexp's group as value
	*/
	abstract public function getRegexpGroups();

	/**
	* Return the regexps associated with this convertor
	*
	* @return array Regexp's name as key, regexp as value
	*/
	abstract public function getRegexps();

	/**
	* Convert given XPath expression to PHP
	*
	* @param  string $expr
	* @return string
	*/
	protected function convert($expr)
	{
		return $this->runner->convert($expr);
	}

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