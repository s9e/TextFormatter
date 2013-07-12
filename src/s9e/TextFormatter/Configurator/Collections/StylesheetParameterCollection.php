<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Items\StaticStylesheetParameter;
use s9e\TextFormatter\Configurator\Items\StylesheetParameter;
use s9e\TextFormatter\Configurator\Validators\StylesheetParameterName;

class StylesheetParameterCollection extends NormalizedCollection
{
	/**
	* {@inheritdoc}
	*/
	public function normalizeKey($key)
	{
		return StylesheetParameterName::normalize($key);
	}

	/**
	* {@inheritdoc}
	*/
	public function normalizeValue($value)
	{
		return ($value instanceof StylesheetParameter)
		      ? $value
		      : new StaticStylesheetParameter($value);
	}
}