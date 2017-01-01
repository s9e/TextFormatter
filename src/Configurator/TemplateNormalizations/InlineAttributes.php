<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMException;
use DOMText;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineAttributes extends TemplateNormalization
{
	/**
	* Inline the attribute declarations of a template
	*
	* Will replace
	*     <a><xsl:attribute name="href"><xsl:value-of select="@url"/></xsl:attribute>...</a>
	* with
	*     <a href="{@url}">...</a>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/xsl:attribute';
		foreach ($xpath->query($query) as $attribute)
		{
			$this->inlineAttribute($attribute);
		}
	}

	/**
	* Inline the content of an xsl:attribute element
	*
	* @param  DOMElement $attribute xsl:attribute element
	* @return void
	*/
	protected function inlineAttribute(DOMElement $attribute)
	{
		$value = '';
		foreach ($attribute->childNodes as $node)
		{
			if ($node instanceof DOMText
			 || [$node->namespaceURI, $node->localName] === [self::XMLNS_XSL, 'text'])
			{
				$value .= preg_replace('([{}])', '$0$0', $node->textContent);
			}
			elseif ([$node->namespaceURI, $node->localName] === [self::XMLNS_XSL, 'value-of'])
			{
				$value .= '{' . $node->getAttribute('select') . '}';
			}
			else
			{
				// Can't inline this attribute
				return;
			}
		}
		$attribute->parentNode->setAttribute($attribute->getAttribute('name'), $value);
		$attribute->parentNode->removeChild($attribute);
	}
}