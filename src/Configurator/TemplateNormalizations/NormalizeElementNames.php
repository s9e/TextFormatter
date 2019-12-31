<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

class NormalizeElementNames extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//*[namespace-uri() != $XSL]',
		'//xsl:element[not(contains(@name, "{"))]'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		if ($this->isXsl($element, 'element'))
		{
			$this->replaceXslElement($element);
		}
		else
		{
			$this->replaceElement($element);
		}
	}

	/**
	* Normalize and replace a non-XSL element if applicable
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function replaceElement(DOMElement $element)
	{
		$elName = $this->lowercase($element->localName);
		if ($elName === $element->localName)
		{
			return;
		}

		// Create a new element with the correct name
		$newElement = (is_null($element->namespaceURI))
		            ? $this->ownerDocument->createElement($elName)
		            : $this->ownerDocument->createElementNS($element->namespaceURI, $elName);

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

	/**
	* Normalize the name used in a xsl:element
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function replaceXslElement(DOMElement $element)
	{
		$elName = $this->lowercase($element->getAttribute('name'));
		$element->setAttribute('name', $elName);
	}
}