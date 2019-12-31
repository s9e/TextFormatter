<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

/**
* Sort attributes by name in lexical order
*
* Only applies to inline attributes, not attributes created with xsl:attribute
*/
class SortAttributesByName extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//*[@*]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$attributes = [];
		foreach ($element->attributes as $name => $attribute)
		{
			$attributes[$name] = $element->removeAttributeNode($attribute);
		}

		ksort($attributes);
		foreach ($attributes as $attribute)
		{
			$element->setAttributeNode($attribute);
		}
	}
}