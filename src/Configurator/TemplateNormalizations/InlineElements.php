<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMException;
class InlineElements extends AbstractNormalization
{
	protected $queries = array('//xsl:element');
	protected function normalizeElement(DOMElement $element)
	{
		$elName = $element->getAttribute('name');
		$dom    = $this->ownerDocument;
		try
		{
			$newElement = ($element->hasAttribute('namespace'))
						? $dom->createElementNS($element->getAttribute('namespace'), $elName)
						: $dom->createElement($elName);
		}
		catch (DOMException $e)
		{
			return;
		}
		$element->parentNode->replaceChild($newElement, $element);
		while ($element->firstChild)
			$newElement->appendChild($element->removeChild($element->firstChild));
	}
}