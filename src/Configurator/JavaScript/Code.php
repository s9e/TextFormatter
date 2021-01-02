<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use s9e\TextFormatter\Configurator\FilterableConfigValue;

/**
* Wrapper used to identify strings that should be treated as JavaScript source code
*/
class Code implements FilterableConfigValue
{
	/**
	* @var string JavaScript source code
	*/
	public $code;

	/**
	* Constructor
	*
	* @param  string $code JavaScript source code
	*/
	public function __construct($code)
	{
		$this->code = $code;
	}

	/**
	* Return this source code
	*
	* @return string
	*/
	public function __toString()
	{
		return (string) $this->code;
	}

	/**
	* {@inheritdoc}
	*/
	public function filterConfig($target)
	{
		return ($target === 'JS') ? $this : null;
	}
}