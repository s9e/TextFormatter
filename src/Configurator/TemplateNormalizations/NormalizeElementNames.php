<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class NormalizeElementNames extends AbstractNormalization
{
	protected $queries = array(
		'//*[namespace-uri() != $XSL]',
		'//xsl:element[not(contains(@name, "{"))]'
	);
	protected function normalizeElement(DOMElement $element)
	{
		if ($this->isXsl($element, 'element'))
			$this->replaceXslElement($element);
		else
			$this->replaceElement($element);
	}
	protected function replaceElement(DOMElement $element)
	{
		$elName = $this->lowercase($element->localName);
		if ($elName === $element->localName)
			return;
		$newElement = (\is_null($element->namespaceURI))
		            ? $this->ownerDocument->createElement($elName)
		            : $this->ownerDocument->createElementNS($element->namespaceURI, $elName);
		while ($element->firstChild)
			$newElement->appendChild($element->removeChild($element->firstChild));
		foreach ($element->attributes as $attribute)
			$newElement->setAttributeNS(
				$attribute->namespaceURI,
				$attribute->nodeName,
				$attribute->value
			);
		$element->parentNode->replaceChild($newElement, $element);
	}
	protected function replaceXslElement(DOMElement $element)
	{
		$elName = $this->lowercase($element->getAttribute('name'));
		$element->setAttribute('name', $elName);
	}
}