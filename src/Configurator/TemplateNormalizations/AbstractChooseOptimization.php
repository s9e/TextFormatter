<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;
use DOMNode;

abstract class AbstractChooseOptimization extends AbstractNormalization
{
	/**
	* @var Element Current xsl:choose element
	*/
	protected $choose;

	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:choose'];

	/**
	* Retrieve a list of attributes from given element
	*
	* @return array NamespaceURI#nodeName as keys, attribute values as values
	*/
	protected function getAttributes(Element $element)
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
	* @return Element[]
	*/
	protected function getBranches(): array
	{
		return iterator_to_array($this->choose->query('xsl:when|xsl:otherwise'));
	}

	/**
	* Test whether current xsl:choose element has an xsl:otherwise child
	*
	* @return bool
	*/
	protected function hasOtherwise()
	{
		return (bool) $this->choose->evaluate('count(xsl:otherwise)');
	}

	/**
	* Test whether current xsl:choose element has no content besides xsl:when and xsl:otherwise
	*
	* @return bool
	*/
	protected function isEmpty()
	{
		return !$this->choose->evaluate('count(xsl:when/node() | xsl:otherwise/node())');
	}

	/**
	* Test whether two elements have the same start tag
	*
	* @param  Element $el1
	* @param  Element $el2
	* @return bool
	*/
	protected function isEqualTag(Element $el1, Element $el2)
	{
		return ($el1->namespaceURI === $el2->namespaceURI && $el1->nodeName === $el2->nodeName && $this->getAttributes($el1) === $this->getAttributes($el2));
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
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
	protected function reset(): void
	{
		$this->choose = null;
		parent::reset();
	}
}