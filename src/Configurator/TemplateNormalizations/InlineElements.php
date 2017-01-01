<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMException;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineElements extends TemplateNormalization
{
	/**
	* Inline the elements declarations of a template
	*
	* Will replace
	*     <xsl:element name="div"><xsl:apply-templates/></xsl:element>
	* with
	*     <div><xsl:apply-templates/></div>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom = $template->ownerDocument;
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'element') as $element)
		{
			$elName = $element->getAttribute('name');

			try
			{
				// Create the new static element
				$newElement = ($element->hasAttribute('namespace'))
				            ? $dom->createElementNS($element->getAttribute('namespace'), $elName)
				            : $dom->createElement($elName);
			}
			catch (DOMException $e)
			{
				// Ignore this element and keep going if an exception got thrown
				continue;
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
}