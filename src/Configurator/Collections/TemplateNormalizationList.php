<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Configurator\TemplateNormalizations\Custom;

class TemplateNormalizationList extends NormalizedList
{
	public function normalizeValue($value)
	{
		if ($value instanceof TemplateNormalization)
			return $value;

		if (\is_callable($value))
			return new Custom($value);

		$className = 's9e\\TextFormatter\\Configurator\\TemplateNormalizations\\' . $value;

		return new $className;
	}
}