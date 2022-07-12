<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

class MergeConsecutiveCopyOf extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//xsl:copy-of'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		while ($this->nextSiblingIsCopyOf($element))
		{
			$element->setAttribute('select', $element->getAttribute('select') . '|' . $element->nextSibling->getAttribute('select'));
			$element->parentNode->removeChild($element->nextSibling);
		}
	}

	/**
	* Test whether the next sibling to given element is an xsl:copy-of element
	*
	* @param  DOMElement $element Context node
	* @return bool
	*/
	protected function nextSiblingIsCopyOf(DOMElement $element)
	{
		return ($element->nextSibling && $this->isXsl($element->nextSibling, 'copy-of'));
	}
}