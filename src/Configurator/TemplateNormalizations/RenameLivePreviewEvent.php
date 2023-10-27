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
* Rename deprecated data-s9e-livepreview-postprocess attributes to data-s9e-livepreview-onrender
*/
class RenameLivePreviewEvent extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//*[@data-s9e-livepreview-postprocess]',
		'//xsl:attribute/@name[. = "data-s9e-livepreview-postprocess"]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(Attr $attribute): void
	{
		$attribute->value = 'data-s9e-livepreview-onrender';
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$value = $element->getAttribute('data-s9e-livepreview-postprocess');
		$element->setAttribute('data-s9e-livepreview-onrender', $value);
		$element->removeAttribute('data-s9e-livepreview-postprocess');
	}
}