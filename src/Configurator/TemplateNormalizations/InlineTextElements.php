<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

class InlineTextElements extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:text[not(@disable-output-escaping="yes")]'];

	/**
	* Test whether an element is followed by a text node
	*
	* @param  Element $element
	* @return bool
	*/
	protected function isFollowedByText(Element $element)
	{
		return ($element->nextSibling && $element->nextSibling->nodeType === XML_TEXT_NODE);
	}

	/**
	* Test whether an element is preceded by a text node
	*
	* @param  Element $element
	* @return bool
	*/
	protected function isPrecededByText(Element $element)
	{
		return ($element->previousSibling && $element->previousSibling->nodeType === XML_TEXT_NODE);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		// If this node's content is whitespace, ensure it's preceded or followed by a text node
		if (trim($element->textContent) === '')
		{
			if (!$this->isFollowedByText($element) && !$this->isPrecededByText($element))
			{
				// This would become inter-element whitespace, therefore we can't inline
				return;
			}
		}
		$element->replaceWith($element->textContent);
	}
}