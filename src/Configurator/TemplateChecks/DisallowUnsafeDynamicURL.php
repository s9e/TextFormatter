<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;
use DOMAttr;
use DOMElement;
use DOMText;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\NodeLocator;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
class DisallowUnsafeDynamicURL extends AbstractDynamicContentCheck
{
	protected $safeUrlRegexp = '(^(?:(?!data|\\w*script)\\w+:|[^:]*/|#))i';
	protected function getNodes(DOMElement $template)
	{
		return NodeLocator::getURLNodes($template->ownerDocument);
	}
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeAsURL();
	}
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		if (!$this->isSafeUrl($attribute->value))
			parent::checkAttributeNode($attribute, $tag);
	}
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		if (!$this->elementHasSafeUrl($element))
			parent::checkElementNode($element, $tag);
	}
	protected function chooseHasSafeUrl(DOMElement $choose)
	{
		$xpath        = new DOMXPath($choose->ownerDocument);
		$hasOtherwise = \false;
		foreach ($xpath->query('xsl:when | xsl:otherwise', $choose) as $branch)
		{
			if (!$this->elementHasSafeUrl($branch))
				return \false;
			if ($branch->nodeName === 'xsl:otherwise')
				$hasOtherwise = \true;
		}
		return $hasOtherwise;
	}
	protected function elementHasSafeUrl(DOMElement $element)
	{
		if ($element->firstChild instanceof DOMElement && $element->firstChild->nodeName === 'xsl:choose')
			return $this->chooseHasSafeUrl($element->firstChild);
		return $element->firstChild instanceof DOMText && $this->isSafeUrl($element->firstChild->textContent);
	}
	protected function isSafeUrl($url)
	{
		return (bool) \preg_match($this->safeUrlRegexp, $url);
	}
}