<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMText;
class InlineAttributes extends AbstractNormalization
{
	protected $queries = array('//*[namespace-uri() != $XSL]/xsl:attribute');
	protected function normalizeElement(DOMElement $element)
	{
		$value = '';
		foreach ($element->childNodes as $node)
			if ($node instanceof DOMText || $this->isXsl($node, 'text'))
				$value .= \preg_replace('([{}])', '$0$0', $node->textContent);
			elseif ($this->isXsl($node, 'value-of'))
				$value .= '{' . $node->getAttribute('select') . '}';
			else
				return;
		$element->parentNode->setAttribute($element->getAttribute('name'), $value);
		$element->parentNode->removeChild($element);
	}
}