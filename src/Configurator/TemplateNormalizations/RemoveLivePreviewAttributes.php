<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;

/**
* Remove attributes related to live preview
*/
class RemoveLivePreviewAttributes extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//@*           [starts-with(name(), "data-s9e-livepreview-")]',
		'//xsl:attribute[starts-with(@name,  "data-s9e-livepreview-")]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$attribute->parentNode->removeAttributeNode($attribute);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$element->parentNode->removeChild($element);
	}
}