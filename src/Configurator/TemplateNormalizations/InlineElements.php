<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMException;

/**
* Inline the elements declarations of a template
*
* Will replace
*     <xsl:element name="div"><xsl:apply-templates/></xsl:element>
* with
*     <div><xsl:apply-templates/></div>
*/
class InlineElements extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//xsl:element'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$elName = $element->getAttribute('name');
		$dom    = $this->ownerDocument;

		try
		{
			// Create the new static element
			$newElement = ($element->hasAttribute('namespace'))
						? $dom->createElementNS($element->getAttribute('namespace'), $elName)
						: $dom->createElement($elName);
		}
		catch (DOMException $e)
		{
			// Ignore this element if an exception got thrown
			return;
		}

		// Replace the old <xsl:element/> with it. We do it now so that libxml doesn't have to
		// redeclare the XSL namespace
		$element->parentNode->replaceChild($newElement, $element);

		// One by one and in order, we move the nodes from the old element to the new one
		while ($element->firstChild)
		{
			$newElement->appendChild($element->removeChild($element->firstChild));
		}
	}
}