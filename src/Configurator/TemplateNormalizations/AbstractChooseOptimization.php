<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMNode;
abstract class AbstractChooseOptimization extends AbstractNormalization
{
	protected $choose;
	protected $queries = array('//xsl:choose');
	protected function getAttributes(DOMElement $element)
	{
		$attributes = array();
		foreach ($element->attributes as $attribute)
		{
			$key = $attribute->namespaceURI . '#' . $attribute->nodeName;
			$attributes[$key] = $attribute->nodeValue;
		}
		return $attributes;
	}
	protected function getBranches()
	{
		$query = 'xsl:when|xsl:otherwise';
		return $this->xpath($query, $this->choose);
	}
	protected function hasOtherwise()
	{
		return (bool) $this->xpath->evaluate('count(xsl:otherwise)', $this->choose);
	}
	protected function isEmpty()
	{
		$query = 'count(xsl:when/node() | xsl:otherwise/node())';
		return !$this->xpath->evaluate($query, $this->choose);
	}
	protected function isEqualNode(DOMNode $node1, DOMNode $node2)
	{
		return ($node1->ownerDocument->saveXML($node1) === $node2->ownerDocument->saveXML($node2));
	}
	protected function isEqualTag(DOMElement $el1, DOMElement $el2)
	{
		return ($el1->namespaceURI === $el2->namespaceURI && $el1->nodeName === $el2->nodeName && $this->getAttributes($el1) === $this->getAttributes($el2));
	}
	protected function normalizeElement(DOMElement $element)
	{
		$this->choose = $element;
		$this->optimizeChoose();
	}
	abstract protected function optimizeChoose();
	protected function reset()
	{
		$this->choose = \null;
		parent::reset();
	}
}