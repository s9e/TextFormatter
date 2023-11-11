<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Attr;
use s9e\SweetDOM\Element;

/**
* Remove attributes related to live preview
*/
class RemoveLivePreviewAttributes extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//@*           [starts-with(name(), "data-s9e-livepreview-")]',
		'//xsl:attribute[starts-with(@name,  "data-s9e-livepreview-")]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(Attr $attribute): void
	{
		$attribute->parentNode->removeAttributeNode($attribute);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$element->remove();
	}
}