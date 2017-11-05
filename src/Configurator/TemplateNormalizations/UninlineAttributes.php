<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

/**
* Uninline element attributes
*
* Will replace
*     <a href="{@url}">...</a>
* with
*     <a><xsl:attribute name="href"><xsl:value-of select="@url"/></xsl:attribute>...</a>
*/
class UninlineAttributes extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//*[namespace-uri() != $XSL]'];

	/**
	* {@inheritdoc}
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
		$xslAttribute  = $this->createElement('xsl:attribute');
		$xslAttribute->setAttribute('name', $attribute->nodeName);

		// Build the content of the xsl:attribute element
		foreach (AVTHelper::parse($attribute->value) as list($type, $content))
		{
			if ($type === 'expression')
			{
				$childNode = $this->createElement('xsl:value-of');
				$childNode->setAttribute('select', $content);
			}
			else
			{
				$childNode = $this->createElement('xsl:text');
				$childNode->appendChild($this->createTextNode($content));
			}

			$xslAttribute->appendChild($childNode);
		}

		// Remove the attribute from its parent element
		$attribute->parentNode->removeAttributeNode($attribute);

		return $xslAttribute;
	}
}