<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

class MergeConsecutiveCopyOf extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:copy-of'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		while ($this->nextSiblingIsCopyOf($element))
		{
			$element->setAttribute('select', $element->getAttribute('select') . '|' . $element->nextSibling->getAttribute('select'));
			$element->nextSibling->remove();
		}
	}

	/**
	* Test whether the next sibling to given element is an xsl:copy-of element
	*
	* @param  Element $element Context node
	* @return bool
	*/
	protected function nextSiblingIsCopyOf(Element $element)
	{
		return ($element->nextSibling && $this->isXsl($element->nextSibling, 'copy-of'));
	}
}