<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use function str_contains, str_replace;
use s9e\SweetDOM\Attr;
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
		$element->prepend($fragment);
	}

	/**
	* Remove an attribute from its parent element and return its content as an xsl:attribute
	*
	* @param  Attr $attribute Attribute node
	* @return Element         xsl:attribute element
	*/
	protected function uninlineAttribute(Attr $attribute)
	{
		$xslAttribute = (str_contains($attribute->value, '{'))
		              ? $this->uninlineDynamicAttribute($attribute)
		              : $this->uninlineStaticAttribute($attribute);

		// Remove the attribute from its parent element
		$attribute->parentNode->removeAttributeNode($attribute);

		return $xslAttribute;
	}

	/**
	* Uninline an AVT-style attribute
	*
	* @param  Attr $attribute Attribute node
	* @return Element         xsl:attribute element
	*/
	protected function uninlineDynamicAttribute(Attr $attribute)
	{
		$xslAttribute = $this->ownerDocument->nodeCreator->createXslAttribute($attribute->nodeName);

		// Build the content of the xsl:attribute element
		foreach (AVTHelper::parse($attribute->value) as [$type, $content])
		{
			if ($type === 'expression')
			{
				$childNode = $this->ownerDocument->nodeCreator->createXslValueOf($content);
			}
			else
			{
				$childNode = $this->createPolymorphicText($content);
			}

			$xslAttribute->appendChild($childNode);
		}

		return $xslAttribute;
	}

	/**
	* Uninline an attribute with a static value
	*
	* @param  Attr $attribute Attribute node
	* @return Element         xsl:attribute element
	*/
	protected function uninlineStaticAttribute(Attr $attribute)
	{
		return $attribute->ownerDocument->nodeCreator->createXslAttribute(
			name:        $attribute->nodeName,
			textContent: str_replace('}}', '}', $attribute->value)
		);
	}
}