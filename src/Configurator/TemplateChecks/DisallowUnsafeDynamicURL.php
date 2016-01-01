<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMAttr;
use DOMElement;
use DOMText;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;

/**
* This primary use of this check is to ensure that dynamic content cannot be used to create
* javascript: links
*/
class DisallowUnsafeDynamicURL extends AbstractDynamicContentCheck
{
	/**
	* @var string Regexp used to exclude nodes that start with a hardcoded scheme part, a hardcoded
	*             local part, or a fragment
	*/
	protected $exceptionRegexp = '(^(?:(?!data|\\w*script)\\w+:|[^:]*/|#))i';

	/**
	* {@inheritdoc}
	*/
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getURLNodes($template->ownerDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeAsURL();
	}

	/**
	* {@inheritdoc}
	*/
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		// Ignore this attribute if its scheme is hardcoded or it starts with //
		if (preg_match($this->exceptionRegexp, $attribute->value))
		{
			return;
		}

		parent::checkAttributeNode($attribute, $tag);
	}

	/**
	* {@inheritdoc}
	*/
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		// Ignore this element if its scheme is hardcoded or it starts with //
		if ($element->firstChild
		 && $element->firstChild instanceof DOMText
		 && preg_match($this->exceptionRegexp, $element->firstChild->textContent))
		{
			return;
		}

		parent::checkElementNode($element, $tag);
	}
}