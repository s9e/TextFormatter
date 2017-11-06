<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class SortAttributesByName extends AbstractNormalization
{
	protected $queries = array('//*[@*]');
	protected function normalizeElement(DOMElement $element)
	{
		$attributes = array();
		foreach ($element->attributes as $name => $attribute)
			$attributes[$name] = $element->removeAttributeNode($attribute);
		\ksort($attributes);
		foreach ($attributes as $attribute)
			$element->setAttributeNode($attribute);
	}
}