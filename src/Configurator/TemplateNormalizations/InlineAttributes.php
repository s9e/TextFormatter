<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;
use s9e\SweetDOM\Text;

/**
* Inline the xsl:attribute declarations of a template
*
* Will replace
*     <a><xsl:attribute name="href"><xsl:value-of select="@url"/></xsl:attribute>...</a>
* with
*     <a href="{@url}">...</a>
*/
class InlineAttributes extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/xsl:attribute'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$value = '';
		foreach ($element->childNodes as $node)
		{
			if ($node instanceof Text || $this->isXsl($node, 'text'))
			{
				$value .= preg_replace('([{}])', '$0$0', $node->textContent);
			}
			elseif ($this->isXsl($node, 'value-of'))
			{
				$value .= '{' . $node->getAttribute('select') . '}';
			}
			else
			{
				// Can't inline this attribute
				return;
			}
		}
		$element->parentNode->setAttribute($element->getAttribute('name'), $value);
		$element->remove();
	}
}