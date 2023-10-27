<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Attr;
use s9e\SweetDOM\Element;

class NormalizeAttributeNames extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//@*[name() != translate(name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")]',
		'//xsl:attribute[not(contains(@name, "{"))][@name != translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(Attr $attribute): void
	{
		$attribute->parentNode->setAttribute($this->lowercase($attribute->localName), $attribute->value);
		$attribute->parentNode->removeAttributeNode($attribute);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$element->setAttribute('name', $this->lowercase($element->getAttribute('name')));
	}
}