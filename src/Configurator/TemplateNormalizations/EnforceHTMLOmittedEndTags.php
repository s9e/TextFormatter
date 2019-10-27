<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\ElementInspector;
class EnforceHTMLOmittedEndTags extends AbstractNormalization
{
	protected $queries = array('//*[namespace-uri() = ""]/*[namespace-uri() = ""]');
	protected function normalizeElement(DOMElement $element)
	{
		$parentNode = $element->parentNode;
		if (ElementInspector::isVoid($parentNode) || ElementInspector::closesParent($element, $parentNode))
			$this->reparentElement($element);
	}
	protected function reparentElement(DOMElement $element)
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