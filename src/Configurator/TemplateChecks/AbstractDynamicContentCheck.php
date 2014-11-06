<?php

/*
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
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\TemplateCheck;

abstract class AbstractDynamicContentCheck extends TemplateCheck
{
	abstract protected function getNodes(DOMElement $template);

	abstract protected function isSafe(Attribute $attribute);

	public function check(DOMElement $template, Tag $tag)
	{
		foreach ($this->getNodes($template) as $node)
			$this->checkNode($node, $tag);
	}

	protected function checkAttribute(DOMNode $node, Tag $tag, $attrName)
	{
		if (!isset($tag->attributes[$attrName]))
			throw new UnsafeTemplateException("Cannot assess the safety of unknown attribute '" . $attrName . "'", $node);

		if (!$this->isSafe($tag->attributes[$attrName]))
			throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly sanitized to be used in this context", $node);
	}

	protected function checkAttributeNode(DOMAttr $attribute, Tag $tag)
	{
		foreach (AVTHelper::parse($attribute->value) as $token)
			if ($token[0] === 'expression')
				$this->checkExpression($attribute, $token[1], $tag);
	}

	protected function checkContext(DOMNode $node)
	{
		$xpath     = new DOMXPath($node->ownerDocument);
		$ancestors = $xpath->query('ancestor::xsl:for-each', $node);

		if ($ancestors->length)
			throw new UnsafeTemplateException("Cannot assess context due to '" . $ancestors->item(0)->nodeName . "'", $node);
	}

	protected function checkCopyOfNode(DOMElement $node, Tag $tag)
	{
		$this->checkSelectNode($node->getAttributeNode('select'), $tag);
	}

	protected function checkElementNode(DOMElement $element, Tag $tag)
	{
		$xpath = new DOMXPath($element->ownerDocument);

		$predicate = ($element->localName === 'attribute') ? '' : '[not(ancestor::xsl:attribute)]';

		$query = './/xsl:value-of' . $predicate;
		foreach ($xpath->query($query, $element) as $valueOf)
			$this->checkSelectNode($valueOf->getAttributeNode('select'), $tag);

		$query = './/xsl:apply-templates' . $predicate;
		foreach ($xpath->query($query, $element) as $applyTemplates)
			throw new UnsafeTemplateException('Cannot allow unfiltered data in this context', $applyTemplates);
	}

	protected function checkExpression(DOMNode $node, $expr, Tag $tag)
	{
		$this->checkContext($node);

		if (\preg_match('/^\\$(\\w+)$/', $expr, $m))
		{
			$this->checkVariable($node, $tag, $m[1]);

			return;
		}

		if ($this->isExpressionSafe($expr))
			return;

		if (\preg_match('/^@(\\w+)$/', $expr, $m))
		{
			$this->checkAttribute($node, $tag, $m[1]);

			return;
		}

		throw new UnsafeTemplateException("Cannot assess the safety of expression '" . $expr . "'", $node);
	}

	protected function checkNode(DOMNode $node, Tag $tag)
	{
		if ($node instanceof DOMAttr)
			$this->checkAttributeNode($node, $tag);
		elseif ($node instanceof DOMElement)
			if ($node->namespaceURI === 'http://www.w3.org/1999/XSL/Transform'
			 && $node->localName    === 'copy-of')
				$this->checkCopyOfNode($node, $tag);
			else
				$this->checkElementNode($node, $tag);
	}

	protected function checkVariable(DOMNode $node, $tag, $qname)
	{
		$xpath = new DOMXPath($node->ownerDocument);

		foreach (['xsl:param', 'xsl:variable'] as $nodeName)
		{
			$query = 'ancestor-or-self::*/'
				   . 'preceding-sibling::' . $nodeName . '[@name="' . $qname . '"][@select]';

			foreach ($xpath->query($query, $node) as $varNode)
			{
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
	}

	protected function checkSelectNode(DOMAttr $select, Tag $tag)
	{
		$this->checkExpression($select, $select->value, $tag);
	}

	protected function isExpressionSafe($expr)
	{
		return \false;
	}
}