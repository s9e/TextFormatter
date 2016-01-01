<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

/**
* Wrapper used to identify strings that should be treated as JavaScript source code
*/
class Code
{
	/**
	* @var string JavaScript source code
	*/
	public $code;

	/**
	* Constructor
	*
	* @param  string $code JavaScript source code
	* @return void
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
		return $this->code;
	}
}