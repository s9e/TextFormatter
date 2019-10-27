<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
class RenameLivePreviewEvent extends AbstractNormalization
{
	protected $queries = array(
		'//*[@data-s9e-livepreview-postprocess]',
		'//xsl:attribute/@name[. = "data-s9e-livepreview-postprocess"]'
	);
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$attribute->value = 'data-s9e-livepreview-onrender';
	}
	protected function normalizeElement(DOMElement $element)
	{
		$value = $element->getAttribute('data-s9e-livepreview-postprocess');
		$element->setAttribute('data-s9e-livepreview-onrender', $value);
		$element->removeAttribute('data-s9e-livepreview-postprocess');
	}
}