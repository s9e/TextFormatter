<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

interface Configurable
{
	public function __get($k);
	public function __set($k, $v);
	public function getOption($optionName);
	public function getOptions();
	public function setOption($optionName, $optionValue);
	public function setOptions(array $options);
}