<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

interface ConfigProvider
{
	/**
	* Return an array-based representation of this object to be used for parsing
	*
	* @return array
	*/
	public function toConfig();
}