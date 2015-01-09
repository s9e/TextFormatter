<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMAttr;
use DOMElement;
use DOMText;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;

class DisallowUnsafeDynamicURL extends AbstractDynamicContentCheck
{
	protected $exceptionRegexp = '(^(?:(?!data|\\w*script)\\w+:|[^:]*/|#))i';

	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getURLNodes($template->ownerDocument);
	}

	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeAsURL();
	}

	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		if (\preg_match($this->exceptionRegexp, $attribute->value))
			return;

		parent::checkAttributeNode($attribute, $tag);
	}

	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		if ($element->firstChild
		 && $element->firstChild instanceof DOMText
		 && \preg_match($this->exceptionRegexp, $element->firstChild->textContent))
			return;

		parent::checkElementNode($element, $tag);
	}
}