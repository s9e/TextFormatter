<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Javascript;

/**
* Wrapper used to identify strings that should be treated as Javascript source code
*/
class Code
{
	/**
	* @var string Javascript source code
	*/
	public $code;

	/**
	* Constructor
	*
	* @param  string $code Javascript source code
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