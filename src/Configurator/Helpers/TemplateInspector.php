<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMElement;
use DOMXPath;
class TemplateInspector
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	protected $allowChildBitfields = array();
	protected $allowsChildElements;
	protected $allowsText;
	protected $branches;
	protected $contentBitfield = "\0";
	protected $defaultBranchBitfield;
	protected $denyDescendantBitfield = "\0";
	protected $dom;
	protected $hasElements = \false;
	protected $hasRootText;
	protected $isBlock = \false;
	protected $isEmpty;
	protected $isFormattingElement;
	protected $isPassthrough = \false;
	protected $isTransparent = \false;
	protected $isVoid;
	protected $leafNodes = array();
	protected $preservesNewLines = \false;
	protected $rootBitfields = array();
	protected $rootNodes = array();
	protected $xpath;
	public function __construct($template)
	{
		$this->dom   = TemplateLoader::load($template);
		$this->xpath = new DOMXPath($this->dom);
		$this->defaultBranchBitfield = ElementInspector::getAllowChildBitfield($this->dom->createElement('div'));
		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}
	public function allowsChild(TemplateInspector $child)
	{
		if (!$this->allowsDescendant($child))
			return \false;
		foreach ($child->rootBitfields as $rootBitfield)
			foreach ($this->allowChildBitfields as $allowChildBitfield)
				if (!self::match($rootBitfield, $allowChildBitfield))
					return \false;
		return ($this->allowsText || !$child->hasRootText);
	}
	public function allowsDescendant(TemplateInspector $descendant)
	{
		if (self::match($descendant->contentBitfield, $this->denyDescendantBitfield))
			return \false;
		return ($this->allowsChildElements || !$descendant->hasElements);
	}
	public function allowsChildElements()
	{
		return $this->allowsChildElements;
	}
	public function allowsText()
	{
		return $this->allowsText;
	}
	public function closesParent(TemplateInspector $parent)
	{
		foreach ($this->rootNodes as $rootNode)
			foreach ($parent->leafNodes as $leafNode)
				if (ElementInspector::closesParent($rootNode, $leafNode))
					return \true;
		return \false;
	}
	public function evaluate($expr, DOMElement $node = \null)
	{
		return $this->xpath->evaluate($expr, $node);
	}
	public function isBlock()
	{
		return $this->isBlock;
	}
	public function isFormattingElement()
	{
		return $this->isFormattingElement;
	}
	public function isEmpty()
	{
		return $this->isEmpty;
	}
	public function isPassthrough()
	{
		return $this->isPassthrough;
	}
	public function isTransparent()
	{
		return $this->isTransparent;
	}
	public function isVoid()
	{
		return $this->isVoid;
	}
	public function preservesNewLines()
	{
		return $this->preservesNewLines;
	}
	protected function analyseContent()
	{
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]';
		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= ElementInspector::getCategoryBitfield($node);
			$this->hasElements = \true;
		}
		$this->isPassthrough = (bool) $this->evaluate('count(//xsl:apply-templates)');
	}
	protected function analyseRootNodes()
	{
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"][not(ancestor::*[namespace-uri() != "' . self::XMLNS_XSL . '"])]';
		foreach ($this->xpath->query($query) as $node)
		{
			$this->rootNodes[] = $node;
			if ($this->elementIsBlock($node))
				$this->isBlock = \true;
			$this->rootBitfields[] = ElementInspector::getCategoryBitfield($node);
		}
		$predicate = '[not(ancestor::*[namespace-uri() != "' . self::XMLNS_XSL . '"])]';
		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';
		$query = '//text()[normalize-space() != ""]' . $predicate
		       . '|//xsl:text[normalize-space() != ""]' . $predicate
		       . '|//xsl:value-of' . $predicate;
		$this->hasRootText = (bool) $this->evaluate('count(' . $query . ')');
	}
	protected function analyseBranches()
	{
		$this->branches = array();
		foreach ($this->xpath->query('//xsl:apply-templates') as $applyTemplates)
		{
			$query            = 'ancestor::*[namespace-uri() != "' . self::XMLNS_XSL . '"]';
			$this->branches[] = \iterator_to_array($this->xpath->query($query, $applyTemplates));
		}
		$this->computeAllowsChildElements();
		$this->computeAllowsText();
		$this->computeBitfields();
		$this->computeFormattingElement();
		$this->computeIsEmpty();
		$this->computeIsTransparent();
		$this->computeIsVoid();
		$this->computePreservesNewLines();
		$this->storeLeafNodes();
	}
	protected function anyBranchHasProperty($methodName)
	{
		foreach ($this->branches as $branch)
			foreach ($branch as $element)
				if (ElementInspector::$methodName($element))
					return \true;
		return \false;
	}
	protected function computeBitfields()
	{
		if (empty($this->branches))
		{
			$this->allowChildBitfields = array("\0");
			return;
		}
		foreach ($this->branches as $branch)
		{
			$branchBitfield = $this->defaultBranchBitfield;
			foreach ($branch as $element)
			{
				if (!ElementInspector::isTransparent($element))
					$branchBitfield = "\0";
				$branchBitfield |= ElementInspector::getAllowChildBitfield($element);
				$this->denyDescendantBitfield |= ElementInspector::getDenyDescendantBitfield($element);
			}
			$this->allowChildBitfields[] = $branchBitfield;
		}
	}
	protected function computeAllowsChildElements()
	{
		$this->allowsChildElements = ($this->anyBranchHasProperty('isTextOnly')) ? \false : !empty($this->branches);
	}
	protected function computeAllowsText()
	{
		foreach (\array_filter($this->branches) as $branch)
			if (ElementInspector::disallowsText(\end($branch)))
			{
				$this->allowsText = \false;
				return;
			}
		$this->allowsText = \true;
	}
	protected function computeFormattingElement()
	{
		foreach ($this->branches as $branch)
			foreach ($branch as $element)
				if (!ElementInspector::isFormattingElement($element) && !$this->isFormattingSpan($element))
				{
					$this->isFormattingElement = \false;
					return;
				}
		$this->isFormattingElement = (bool) \count(\array_filter($this->branches));
	}
	protected function computeIsEmpty()
	{
		$this->isEmpty = ($this->anyBranchHasProperty('isEmpty')) || empty($this->branches);
	}
	protected function computeIsTransparent()
	{
		foreach ($this->branches as $branch)
			foreach ($branch as $element)
				if (!ElementInspector::isTransparent($element))
				{
					$this->isTransparent = \false;
					return;
				}
		$this->isTransparent = !empty($this->branches);
	}
	protected function computeIsVoid()
	{
		$this->isVoid = ($this->anyBranchHasProperty('isVoid')) || empty($this->branches);
	}
	protected function computePreservesNewLines()
	{
		foreach ($this->branches as $branch)
		{
			$style = '';
			foreach ($branch as $element)
				$style .= $this->getStyle($element, \true);
			if (\preg_match('(.*white-space\\s*:\\s*(no|pre))is', $style, $m) && \strtolower($m[1]) === 'pre')
			{
				$this->preservesNewLines = \true;
				return;
			}
		}
		$this->preservesNewLines = \false;
	}
	protected function elementIsBlock(DOMElement $element)
	{
		$style = $this->getStyle($element);
		if (\preg_match('(\\bdisplay\\s*:\\s*block)i', $style))
			return \true;
		if (\preg_match('(\\bdisplay\\s*:\\s*(?:inli|no)ne)i', $style))
			return \false;
		return ElementInspector::isBlock($element);
	}
	protected function getStyle(DOMElement $node, $deep = \false)
	{
		$style = '';
		if (ElementInspector::preservesWhitespace($node))
			$style .= 'white-space:pre;';
		$style .= $node->getAttribute('style');
		$query = (($deep) ? './/' : './') . 'xsl:attribute[@name="style"]';
		foreach ($this->xpath->query($query, $node) as $attribute)
			$style .= ';' . $attribute->textContent;
		return $style;
	}
	protected function isFormattingSpan(DOMElement $node)
	{
		if ($node->nodeName !== 'span')
			return \false;
		if ($node->getAttribute('class') === '' && $node->getAttribute('style') === '')
			return \false;
		foreach ($node->attributes as $attrName => $attribute)
			if ($attrName !== 'class' && $attrName !== 'style')
				return \false;
		return \true;
	}
	protected function storeLeafNodes()
	{
		foreach (\array_filter($this->branches) as $branch)
			$this->leafNodes[] = \end($branch);
	}
	protected static function match($bitfield1, $bitfield2)
	{
		return (\trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}