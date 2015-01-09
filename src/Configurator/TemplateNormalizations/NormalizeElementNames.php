<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class NormalizeElementNames extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//*[namespace-uri() != "' . self::XMLNS_XSL . '"]') as $element)
		{
			$elName = self::lowercase($element->localName);

			if ($elName === $element->localName)
				continue;

			$newElement = (\is_null($element->namespaceURI))
			            ? $dom->createElement($elName)
			            : $dom->createElementNS($element->namespaceURI, $elName);

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

		foreach ($xpath->query('//xsl:element[not(contains(@name, "{"))]') as $element)
		{
			$elName = self::lowercase($element->getAttribute('name'));

			$element->setAttribute('name', $elName);
		}
	}
}