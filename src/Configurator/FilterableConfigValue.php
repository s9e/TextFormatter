<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator;

interface FilterableConfigValue
{
	/**
	* Return the config value for given target
	*
	* @param  $target
	* @return mixed
	*/
	public function filterConfig($target);
}