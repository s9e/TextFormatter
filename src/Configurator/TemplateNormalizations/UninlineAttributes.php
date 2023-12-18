<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use function array_reverse, str_contains, str_replace;
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
		$attributes = [];
		foreach ($element->attributes as $attribute)
		{
			$attributes[$attribute->name] = $attribute->value;
		}
		foreach (array_reverse($attributes) as $attrName => $attrValue)
		{
			if (str_contains($attrValue, '{'))
			{
				$element->prependXslAttribute($attrName)
				        ->append(...$this->getAttributeChildren($attrValue));
			}
			else
			{
				$element->prependXslAttribute($attrName, str_replace('}}', '}', $attrValue));
			}
			$element->removeAttribute($attrName);
		}
	}

	/**
	* Uninline an AVT-style attribute
	*
	* @param  string $attrValue Attribute value
	* @return array             List of strings/nodes
	*/
	protected function getAttributeChildren(string $attrValue): array
	{
		$children = [];

		// Build the content of the xsl:attribute element
		foreach (AVTHelper::parse($attrValue) as [$type, $content])
		{
			if ($type === 'expression')
			{
				$children[] = $this->ownerDocument->nodeCreator->createXslValueOf($content);
			}
			else
			{
				$children[] = $this->createPolymorphicText($content);
			}
		}

		return $children;
	}
}