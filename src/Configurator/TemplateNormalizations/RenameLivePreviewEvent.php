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
* Rename deprecated data-s9e-livepreview-postprocess attributes to data-s9e-livepreview-onrender
*/
class RenameLivePreviewEvent extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//*[@data-s9e-livepreview-postprocess]',
		'//xsl:attribute/@name[. = "data-s9e-livepreview-postprocess"]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$attribute->value = 'data-s9e-livepreview-onrender';
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$value = $element->getAttribute('data-s9e-livepreview-postprocess');
		$element->setAttribute('data-s9e-livepreview-onrender', $value);
		$element->removeAttribute('data-s9e-livepreview-postprocess');
	}
}