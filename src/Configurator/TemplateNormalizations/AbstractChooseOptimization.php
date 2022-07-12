<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMNode;

abstract class AbstractChooseOptimization extends AbstractNormalization
{
	/**
	* @var DOMElement Current xsl:choose element
	*/
	protected $choose;

	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//xsl:choose'];

	/**
	* Retrieve a list of attributes from given element
	*
	* @return array NamespaceURI#nodeName as keys, attribute values as values
	*/
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

	/**
	* Return a list the xsl:when and xsl:otherwise children of current xsl:choose element
	*
	* @return DOMElement[]
	*/
	protected function getBranches()
	{
		$query = 'xsl:when|xsl:otherwise';

		return $this->xpath($query, $this->choose);
	}

	/**
	* Test whether current xsl:choose element has an xsl:otherwise child
	*
	* @return bool
	*/
	protected function hasOtherwise()
	{
		return (bool) $this->xpath->evaluate('count(xsl:otherwise)', $this->choose);
	}

	/**
	* Test whether current xsl:choose element has no content besides xsl:when and xsl:otherwise
	*
	* @return bool
	*/
	protected function isEmpty()
	{
		$query = 'count(xsl:when/node() | xsl:otherwise/node())';

		return !$this->xpath->evaluate($query, $this->choose);
	}

	/**
	* Test whether two nodes are identical
	*
	* ext/dom does not support isEqualNode() from DOM Level 3 so this is a makeshift replacement.
	* Unlike the DOM 3 function, attributes order matters
	*
	* @param  DOMNode $node1
	* @param  DOMNode $node2
	* @return bool
	*/
	protected function isEqualNode(DOMNode $node1, DOMNode $node2)
	{
		return ($node1->ownerDocument->saveXML($node1) === $node2->ownerDocument->saveXML($node2));
	}

	/**
	* Test whether two elements have the same start tag
	*
	* @param  DOMElement $el1
	* @param  DOMElement $el2
	* @return bool
	*/
	protected function isEqualTag(DOMElement $el1, DOMElement $el2)
	{
		return ($el1->namespaceURI === $el2->namespaceURI && $el1->nodeName === $el2->nodeName && $this->getAttributes($el1) === $this->getAttributes($el2));
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$this->choose = $element;
		$this->optimizeChoose();
	}

	/**
	* Optimize the current xsl:choose element
	*
	* @return void
	*/
	abstract protected function optimizeChoose();

	/**
	* {@inheritdoc}
	*/
	protected function reset()
	{
		$this->choose = null;
		parent::reset();
	}
}