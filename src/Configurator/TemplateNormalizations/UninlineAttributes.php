<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
class UninlineAttributes extends AbstractNormalization
{
	protected $queries = array('//*[namespace-uri() != $XSL][@*]');
	protected function normalizeElement(DOMElement $element)
	{
		$fragment = $element->ownerDocument->createDocumentFragment();
		while ($element->attributes->length > 0)
			$fragment->appendChild($this->uninlineAttribute($element->attributes->item(0)));
		$element->insertBefore($fragment, $element->firstChild);
	}
	protected function uninlineAttribute(DOMAttr $attribute)
	{
		$xslAttribute = (\strpos($attribute->value, '{') === \false)
		              ? $this->uninlineStaticAttribute($attribute)
		              : $this->uninlineDynamicAttribute($attribute);
		$xslAttribute->setAttribute('name', $attribute->nodeName);
		$attribute->parentNode->removeAttributeNode($attribute);
		return $xslAttribute;
	}
	protected function uninlineDynamicAttribute(DOMAttr $attribute)
	{
		$xslAttribute = $this->createElement('xsl:attribute');
		foreach (AVTHelper::parse($attribute->value) as $_f6b3b659)
		{
			list($type, $content) = $_f6b3b659;
			if ($type === 'expression')
			{
				$childNode = $this->createElement('xsl:value-of');
				$childNode->setAttribute('select', $content);
			}
			else
				$childNode = $this->createText($content);
			$xslAttribute->appendChild($childNode);
		}
		return $xslAttribute;
	}
	protected function uninlineStaticAttribute(DOMAttr $attribute)
	{
		return $this->createElement('xsl:attribute', \str_replace('}}', '}', $attribute->value));
	}
}