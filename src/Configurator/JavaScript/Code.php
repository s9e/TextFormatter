<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;
use s9e\TextFormatter\Configurator\FilterableConfigValue;
class Code implements FilterableConfigValue
{
	public $code;
	public function __construct($code)
	{
		$this->code = $code;
	}
	public function __toString()
	{
		return (string) $this->code;
	}
	public function filterConfig($target)
	{
		return ($target === 'JS') ? $this : \null;
	}
}