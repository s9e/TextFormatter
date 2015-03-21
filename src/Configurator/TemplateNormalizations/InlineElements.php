<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMException;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineElements extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom = $template->ownerDocument;
		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'element') as $element)
		{
			$elName = $element->getAttribute('name');

			try
			{
				$newElement = ($element->hasAttribute('namespace'))
				            ? $dom->createElementNS($element->getAttribute('namespace'), $elName)
				            : $dom->createElement($elName);
			}
			catch (DOMException $e)
			{
				continue;
			}

			$element->parentNode->replaceChild($newElement, $element);

			while ($element->firstChild)
				$newElement->appendChild($element->removeChild($element->firstChild));
		}
	}
}