<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

abstract class AbstractDynamicContentCheck extends TemplateCheck
{
	/**
	* Get the nodes targeted by this check
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return array             Array of DOMElement instances
	*/
	abstract protected function getNodes(DOMElement $template);

	/**
	* Return whether an attribute is considered safe
	*
	* @param  Attribute $attribute Attribute
	* @return bool
	*/
	abstract protected function isSafe(Attribute $attribute);

	/**
	* Look for improperly-filtered dynamic content
	*
	* @param  DOMElement $template <xsl:template/> node
	* @param  Tag        $tag      Tag this template belongs to
	* @return void
	*/
	public function check(DOMElement $template, Tag $tag)
	{
		foreach ($this->getNodes($template) as $node)
		{
			// Test this node's safety
			$this->checkNode($node, $tag);
		}
	}

	/**
	* Test whether a node's context can be safely assessed
	*
	* @param  DOMNode $node Source node
	* @return void
	*/
	protected function checkContext(DOMNode $node)
	{
		// Test whether we know in what context this node is used. An <xsl:for-each/> ancestor would // change this node's context
		$xpath     = new DOMXPath($node->ownerDocument);
		$ancestors = $xpath->query('ancestor::xsl:for-each', $node);

		if ($ancestors->length)
		{
			throw new UnsafeTemplateException("Cannot assess context due to '" . $ancestors->item(0)->nodeName . "'", $node);
		}
	}

	/**
	* Test whether an attribute node is safe
	*
	* @param  DOMAttr $attribute Attribute node
	* @param  Tag     $tag       Reference tag
	* @return void
	*/
	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		// Parse the attribute value for XPath expressions and assess their safety
		foreach (TemplateHelper::parseAttributeValueTemplate($attribute->value) as $token)
		{
			if ($token[0] === 'expression')
			{
				$this->checkExpression($attribute, $token[1], $tag);
			}
		}
	}

	/**
	* Test whether an <xsl:copy-of/> node is safe
	*
	* @param  DOMElement $node <xsl:copy-of/> node
	* @param  Tag        $tag  Reference tag
	* @return void
	*/
	protected function checkCopyOfNode(DOMElement $node, Tag $tag)
	{
		$this->checkSelectNode($node->getAttributeNode('select'), $tag);
	}

	/**
	* Test whether an element node is safe
	*
	* @param  DOMElement $element Element
	* @param  Tag        $tag     Reference tag
	* @return void
	*/
	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		$xpath = new DOMXPath($element->ownerDocument);

		// If current node is not an <xsl:attribute/> element, we exclude descendants
		// with an <xsl:attribute/> ancestor so that content such as:
		//   <script><xsl:attribute name="id"><xsl:value-of/></xsl:attribute></script>
		// would not trigger a false-positive due to the presence of an <xsl:value-of/>
		// element in a <script>
		$predicate = ($element->localName === 'attribute') ? '' : '[not(ancestor::xsl:attribute)]';

		// Test the select expression of <xsl:value-of/> nodes
		$query = './/xsl:value-of' . $predicate;
		foreach ($xpath->query($query, $element) as $valueOf)
		{
			$this->checkSelectNode($valueOf->getAttributeNode('select'), $tag);
		}

		// Reject all <xsl:apply-templates/> nodes
		$query = './/xsl:apply-templates' . $predicate;
		foreach ($xpath->query($query, $element) as $applyTemplates)
		{
			throw new UnsafeTemplateException('Cannot allow unfiltered data in this context', $applyTemplates);
		}
	}

	/**
	* Test the safety of an XPath expression
	*
	* @param  DOMNode $node Source node
	* @param  string  $expr XPath expression
	* @param  Tag     $tag  Source tag
	* @return void
	*/
	protected function checkExpression(DOMNode $node, $expr, Tag $tag)
	{
		$this->checkContext($node);

		// Consider stylesheet parameters safe but test local variables/params
		if (preg_match('/^\\$(\\w+)$/', $expr, $m))
		{
			$xpath = new DOMXPath($node->ownerDocument);
			$qname = $m[1];

			// Test whether this variable comes from a previous xsl:param or xsl:variable element
			foreach (['xsl:param', 'xsl:variable'] as $nodeName)
			{
				$query = 'ancestor-or-self::*/'
				       . 'preceding-sibling::' . $nodeName . '[@name="' . $qname . '"][@select]';

				foreach ($xpath->query($query, $node) as $varNode)
				{
					// Intercept the UnsafeTemplateException and change the node to the one we're
					// really checking before rethrowing it
					try
					{
						$this->checkExpression($varNode, $varNode->getAttribute('select'), $tag);
					}
					catch (UnsafeTemplateException $e)
					{
						$e->setNode($node);

						throw $e;
					}
				}
			}

			// Either this expression came from a variable that is considered safe, or it's a
			// stylesheet parameters, which are considered safe by default
			return;
		}

		// Test whether the expression contains one single attribute
		if (!preg_match('/^@(\\w+)$/', $expr, $m))
		{
			throw new UnsafeTemplateException("Cannot assess the safety of expression '" . $expr . "'", $node);
		}

		$attrName = $m[1];

		// Test whether the attribute exists
		if (!isset($tag->attributes[$attrName]))
		{
			throw new UnsafeTemplateException("Cannot assess the safety of unknown attribute '" . $attrName . "'", $node);
		}

		// Test whether the attribute is safe to be used in this content type
		if (!$this->isSafe($tag->attributes[$attrName]))
		{
			throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly sanitized to be used in this context", $node);
		}
	}

	/**
	* Test whether a node is safe
	*
	* @param  DOMNode $node Source node
	* @param  Tag     $tag  Reference tag
	* @return void
	*/
	protected function checkNode(DOMNode $node, Tag $tag)
	{
		if ($node instanceof DOMAttr)
		{
			$this->checkAttributeNode($node, $tag);
		}
		elseif ($node instanceof DOMElement)
		{
			if ($node->namespaceURI === 'http://www.w3.org/1999/XSL/Transform'
			 && $node->localName    === 'copy-of')
			{
				$this->checkCopyOfNode($node, $tag);
			}
			else
			{
				$this->checkElementNode($node, $tag);
			}
		}
	}

	/**
	* Test whether a select attribute of a node is safe
	*
	* @param  DOMAttr $select Select attribute node
	* @param  Tag     $tag    Reference tag
	* @return void
	*/
	protected function checkSelectNode(DOMAttr $select, Tag $tag)
	{
		$this->checkExpression($select, $select->value, $tag);
	}
}