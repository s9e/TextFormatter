<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
class NormalizeAttributeNames extends AbstractNormalization
{
	protected $queries = array(
		'//@*[name() != translate(name(), "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")]',
		'//xsl:attribute[not(contains(@name, "{"))][@name != translate(@name, "ABCDEFGHIJKLMNOPQRSTUVWXYZ", "abcdefghijklmnopqrstuvwxyz")]'
	);
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$attribute->parentNode->setAttribute($this->lowercase($attribute->localName), $attribute->value);
		$attribute->parentNode->removeAttributeNode($attribute);
	}
	protected function normalizeElement(DOMElement $element)
	{
		$element->setAttribute('name', $this->lowercase($element->getAttribute('name')));
	}
}