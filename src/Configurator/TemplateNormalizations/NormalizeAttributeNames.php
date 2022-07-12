<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;

class NormalizeAttributeNames extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//@*[name() != translate(name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")]',
		'//xsl:attribute[not(contains(@name, "{"))][@name != translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$attribute->parentNode->setAttribute($this->lowercase($attribute->localName), $attribute->value);
		$attribute->parentNode->removeAttributeNode($attribute);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$element->setAttribute('name', $this->lowercase($element->getAttribute('name')));
	}
}