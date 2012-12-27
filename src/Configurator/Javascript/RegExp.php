<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Javascript;

use ArrayObject;

class RegExp
{
	/**
	* @var string This regexp's flags
	*/
	public $flags;

	/**
	* @var array Capturing subpatterns' names
	*/
	public $map = array();

	/**
	* @var string Regexp
	*/
	public $regexp;

	/**
	* Constructor
	*
	* @param  string $regexp Regexp (with no delimiters)
	* @param  string $flags  Regexp's flags
	* @return void
	*/
	public function __construct($regexp, $flags = '')
	{
		$this->regexp = $regexp;
		$this->flags  = $flags;
	}

	/**
	* Return this regexp as a Javascript regexp literal
	*
	* @return string
	*/
	public function __toString()
	{
		return '/' . $this->regexp . '/' . $this->flags;
	}
}