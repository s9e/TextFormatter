<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
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
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/xsl:attribute';

		foreach ($xpath->query($query) as $attribute)
			$this->inlineAttribute($attribute);
	}

	protected function inlineAttribute(DOMElement $attribute)
	{
		$value = '';
		foreach ($attribute->childNodes as $childNode)
			if ($childNode instanceof DOMText)
				$value .= \preg_replace('([{}])', '$0$0', $childNode->textContent);
			elseif ($childNode->namespaceURI === self::XMLNS_XSL
			     && $childNode->localName    === 'value-of')
				$value .= '{' . $childNode->getAttribute('select') . '}';
			elseif ($childNode->namespaceURI === self::XMLNS_XSL
			     && $childNode->localName    === 'text')
				$value .= \preg_replace('([{}])', '$0$0', $childNode->textContent);
			else
				return;

		$attribute->parentNode->setAttribute($attribute->getAttribute('name'), $value);
		$attribute->parentNode->removeChild($attribute);
	}
}