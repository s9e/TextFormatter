<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;
use s9e\TextFormatter\Configurator\Helpers\ElementInspector;

/**
* Enforce omitted/optional HTML 5 end tags and fix the DOM
*
* Will replace
*     <p>.<p>.</p></p>
* with
*     <p>.</p><p>.</p>
*/
class EnforceHTMLOmittedEndTags extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//*[namespace-uri() = ""]/*[namespace-uri() = ""]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$parentNode = $element->parentNode;
		if (ElementInspector::isVoid($parentNode) || ElementInspector::closesParent($element, $parentNode))
		{
			$this->reparentElement($element);
		}
	}

	/**
	* Move given element and its following siblings after its parent element
	*
	* @param  Element $element First element to move
	* @return void
	*/
	protected function reparentElement(Element $element)
	{
		$parentNode = $element->parentNode;
		do
		{
			$lastChild = $parentNode->lastChild;
			$parentNode->parentNode->insertBefore($lastChild, $parentNode->nextSibling);
		}
		while (!$lastChild->isSameNode($element));
	}
}