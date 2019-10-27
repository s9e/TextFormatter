<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;
use s9e\TextFormatter\Configurator\Validators\TemplateParameterName;
class TemplateParameterCollection extends NormalizedCollection
{
	public function normalizeKey($key)
	{
		return TemplateParameterName::normalize($key);
	}
	public function normalizeValue($value)
	{
		return (string) $value;
	}
}