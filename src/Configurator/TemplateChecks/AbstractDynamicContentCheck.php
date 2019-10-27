<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
	protected $ignoreUnknownAttributes = \false;
	abstract protected function getNodes(DOMElement $template);
	abstract protected function isSafe(Attribute $attribute);
	public function check(DOMElement $template, Tag $tag)
	{
		foreach ($this->getNodes($template) as $node)
			$this->checkNode($node, $tag);
	}
	public function detectUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = \false;
	}
	public function ignoreUnknownAttributes()
	{
		$this->ignoreUnknownAttributes = \true;
	}
	protected function checkAttribute(DOMNode $node, Tag $tag, $attrName)
	{
		if (!isset($tag->attributes[$attrName]))
		{
			if ($this->ignoreUnknownAttributes)
				return;
			throw new UnsafeTemplateException("Cannot assess the safety of unknown attribute '" . $attrName . "'", $node);
		}
		if (!$this->tagFiltersAttributes($tag) || !$this->isSafe($tag->attributes[$attrName]))
			throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly sanitized to be used in this context", $node);
	}
	protected function checkAttributeExpression(DOMNode $node, Tag $tag, $expr)
	{
		\preg_match_all('(@([-\\w]+))', $expr, $matches);
		foreach ($matches[1] as $attrName)
			$this->checkAttribute($node, $tag, $attrName);
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
			$this->checkVariable($node, $tag, $m[1]);
		elseif (\preg_match('/^@[-\\w]+(?:\\s*\\|\\s*@[-\\w]+)*$/', $expr))
			$this->checkAttributeExpression($node, $tag, $expr);
		elseif (!$this->isExpressionSafe($expr))
			throw new UnsafeTemplateException("Cannot assess the safety of expression '" . $expr . "'", $node);
	}
	protected function checkNode(DOMNode $node, Tag $tag)
	{
		if ($node instanceof DOMAttr)
			$this->checkAttributeNode($node, $tag);
		elseif ($node instanceof DOMElement)
			if ($node->namespaceURI === self::XMLNS_XSL && $node->localName === 'copy-of')
				$this->checkCopyOfNode($node, $tag);
			else
				$this->checkElementNode($node, $tag);
	}
	protected function checkVariable(DOMNode $node, $tag, $qname)
	{
		$this->checkVariableDeclaration($node, $tag, 'xsl:param[@name="' . $qname . '"]');
		$this->checkVariableDeclaration($node, $tag, 'xsl:variable[@name="' . $qname . '"]');
	}
	protected function checkVariableDeclaration(DOMNode $node, $tag, $query)
	{
		$query = 'ancestor-or-self::*/preceding-sibling::' . $query . '[@select]';
		$xpath = new DOMXPath($node->ownerDocument);
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
	protected function checkSelectNode(DOMAttr $select, Tag $tag)
	{
		$this->checkExpression($select, $select->value, $tag);
	}
	protected function isExpressionSafe($expr)
	{
		return \false;
	}
	protected function tagFiltersAttributes(Tag $tag)
	{
		return $tag->filterChain->containsCallback('s9e\\TextFormatter\\Parser\\FilterProcessing::filterAttributes');
	}
}