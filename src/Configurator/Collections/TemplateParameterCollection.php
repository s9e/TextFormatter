<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Validators\TemplateParameterName;

class TemplateParameterCollection extends NormalizedCollection
{
	/**
	* Normalize a parameter name
	*
	* @param  string $key
	* @return string
	*/
	public function normalizeKey($key)
	{
		return TemplateParameterName::normalize($key);
	}

	/**
	* Normalize a parameter value
	*
	* @param  mixed  $value
	* @return string
	*/
	public function normalizeValue($value)
	{
		return (string) $value;
	}
}