<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

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
	protected array $queries = ['//*[@*]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
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