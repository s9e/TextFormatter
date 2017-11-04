<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class UninlineAttributes extends TemplateNormalization
{
	/**
	* Uninline element attributes
	*
	* Will replace
	*     <a href="{@url}">...</a>
	* with
	*     <a><xsl:attribute name="href"><xsl:value-of select="@url"/></xsl:attribute>...</a>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]';
		foreach ($xpath->query($query) as $element)
		{
			$this->normalizeElement($element);
		}
	}

	/**
	* Uninline an element's attributes
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$firstChild = $element->firstChild;
		while ($element->attributes->length > 0)
		{
			$attribute = $element->attributes->item(0);
			$element->insertBefore($this->uninlineAttribute($attribute), $firstChild);
		}
	}

	/**
	* Remove an attribute from its parent element and return its content as an xsl:attribute
	*
	* @param  DOMAttr    $attribute Attribute node
	* @return DOMElement            xsl:attribute element
	*/
	protected function uninlineAttribute(DOMAttr $attribute)
	{
		$ownerDocument = $attribute->ownerDocument;
		$xslAttribute  = $ownerDocument->createElementNS(self::XMLNS_XSL, 'xsl:attribute');
		$xslAttribute->setAttribute('name', $attribute->nodeName);

		// Build the content of the xsl:attribute element
		foreach (AVTHelper::parse($attribute->value) as list($type, $content))
		{
			if ($type === 'expression')
			{
				$childNode = $ownerDocument->createElementNS(self::XMLNS_XSL, 'xsl:value-of');
				$childNode->setAttribute('select', $content);
			}
			else
			{
				$childNode = $ownerDocument->createElementNS(self::XMLNS_XSL, 'xsl:text');
				$childNode->appendChild($ownerDocument->createTextNode($content));
			}

			$xslAttribute->appendChild($childNode);
		}

		// Remove the attribute from its parent element
		$attribute->parentNode->removeAttributeNode($attribute);

		return $xslAttribute;
	}
}