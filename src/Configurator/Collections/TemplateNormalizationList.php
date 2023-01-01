<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\TemplateNormalizations\AbstractNormalization;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;

class TemplateNormalizationList extends NormalizedList
{
	/**
	* Normalize the value to an instance of AbstractNormalization
	*
	* @param  mixed                 $value Either a string, or an instance of AbstractNormalization
	* @return AbstractNormalization        An instance of AbstractNormalization
	*/
	public function normalizeValue($value)
	{
		if ($value instanceof AbstractNormalization)
		{
			return $value;
		}

		if (is_callable($value))
		{
			return new Custom($value);
		}

		$className = 's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\' . $value;

		return new $className;
	}
}