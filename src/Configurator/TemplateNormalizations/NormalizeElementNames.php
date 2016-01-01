<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class NormalizeElementNames extends TemplateNormalization
{
	/**
	* Lowercase element names
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);

		// Normalize elements' names
		foreach ($xpath->query('//*[namespace-uri() != "' . self::XMLNS_XSL . '"]') as $element)
		{
			$elName = self::lowercase($element->localName);

			if ($elName === $element->localName)
			{
				continue;
			}

			// Create a new element with the correct name
			$newElement = (is_null($element->namespaceURI))
			            ? $dom->createElement($elName)
			            : $dom->createElementNS($element->namespaceURI, $elName);

			// Move every child to the new element
			while ($element->firstChild)
			{
				$newElement->appendChild($element->removeChild($element->firstChild));
			}

			// Copy attributes to the new node
			foreach ($element->attributes as $attribute)
			{
				$newElement->setAttributeNS(
					$attribute->namespaceURI,
					$attribute->nodeName,
					$attribute->value
				);
			}

			// Replace the old element with the new one
			$element->parentNode->replaceChild($newElement, $element);
		}

		// Normalize <xsl:element/> names
		foreach ($xpath->query('//xsl:element[not(contains(@name, "{"))]') as $element)
		{
			$elName = self::lowercase($element->getAttribute('name'));

			$element->setAttribute('name', $elName);
		}
	}
}