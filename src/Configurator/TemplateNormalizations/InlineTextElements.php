<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class InlineTextElements extends AbstractNormalization
{
	protected $queries = array('//xsl:text[not(@disable-output-escaping="yes")]');
	protected function isFollowedByText(DOMElement $element)
	{
		return ($element->nextSibling && $element->nextSibling->nodeType === \XML_TEXT_NODE);
	}
	protected function isPrecededByText(DOMElement $element)
	{
		return ($element->previousSibling && $element->previousSibling->nodeType === \XML_TEXT_NODE);
	}
	protected function normalizeElement(DOMElement $element)
	{
		if (\trim($element->textContent) === '')
			if (!$this->isFollowedByText($element) && !$this->isPrecededByText($element))
				return;
		$element->parentNode->replaceChild($this->createTextNode($element->textContent), $element);
	}
}