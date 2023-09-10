<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use s9e\SweetDOM\Element;
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
	protected array $queries = ['//*[namespace-uri() != "' . self::XMLNS_XSL . '"][@*]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		// Using a document fragment improves performance with multiple attributes
		$fragment = $element->ownerDocument->createDocumentFragment();
		while ($element->attributes->length > 0)
		{
			$fragment->appendChild($this->uninlineAttribute($element->attributes->item(0)));
		}
		$element->insertBefore($fragment, $element->firstChild);
	}

	/**
	* Remove an attribute from its parent element and return its content as an xsl:attribute
	*
	* @param  DOMAttr $attribute Attribute node
	* @return Element            xsl:attribute element
	*/
	protected function uninlineAttribute(DOMAttr $attribute)
	{
		$xslAttribute = (strpos($attribute->value, '{') === false)
		              ? $this->uninlineStaticAttribute($attribute)
		              : $this->uninlineDynamicAttribute($attribute);

		// Remove the attribute from its parent element
		$attribute->parentNode->removeAttributeNode($attribute);

		return $xslAttribute;
	}

	/**
	* Uninline an AVT-style attribute
	*
	* @param  DOMAttr $attribute Attribute node
	* @return Element            xsl:attribute element
	*/
	protected function uninlineDynamicAttribute(DOMAttr $attribute)
	{
		$xslAttribute = $this->ownerDocument->createXslAttribute($attribute->nodeName);

		// Build the content of the xsl:attribute element
		foreach (AVTHelper::parse($attribute->value) as list($type, $content))
		{
			if ($type === 'expression')
			{
				$childNode = $this->ownerDocument->createXslValueOf($content);
			}
			else
			{
				$childNode = $this->createText($content);
			}

			$xslAttribute->appendChild($childNode);
		}

		return $xslAttribute;
	}

	/**
	* Uninline an attribute with a static value
	*
	* @param  DOMAttr $attribute Attribute node
	* @return Element            xsl:attribute element
	*/
	protected function uninlineStaticAttribute(DOMAttr $attribute)
	{
		return $this->ownerDocument->createXslAttribute($attribute->nodeName, str_replace('}}', '}', $attribute->value));
	}
}