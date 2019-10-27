<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateLoader;
class Parser extends IRProcessor
{
	protected $normalizer;
	public function __construct(Normalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}
	public function parse($template)
	{
		$dom = TemplateLoader::load($template);
		$ir = new DOMDocument;
		$ir->loadXML('<template/>');
		$this->createXPath($dom);
		$this->parseChildren($ir->documentElement, $dom->documentElement);
		$this->normalizer->normalize($ir);
		return $ir;
	}
	protected function appendAVT(DOMElement $parentNode, $avt)
	{
		foreach (AVTHelper::parse($avt) as $token)
			if ($token[0] === 'expression')
				$this->appendXPathOutput($parentNode, $token[1]);
			else
				$this->appendLiteralOutput($parentNode, $token[1]);
	}
	protected function appendLiteralOutput(DOMElement $parentNode, $content)
	{
		if ($content === '')
			return;
		$this->appendElement($parentNode, 'output', \htmlspecialchars($content))
		     ->setAttribute('type', 'literal');
	}
	protected function appendConditionalAttributes(DOMElement $parentNode, $expr)
	{
		\preg_match_all('(@([-\\w]+))', $expr, $matches);
		foreach ($matches[1] as $attrName)
		{
			$switch = $this->appendElement($parentNode, 'switch');
			$case   = $this->appendElement($switch, 'case');
			$case->setAttribute('test', '@' . $attrName);
			$attribute = $this->appendElement($case, 'attribute');
			$attribute->setAttribute('name', $attrName);
			$this->appendXPathOutput($attribute, '@' . $attrName);
		}
	}
	protected function appendXPathOutput(DOMElement $parentNode, $expr)
	{
		$this->appendElement($parentNode, 'output', \htmlspecialchars(\trim($expr)))
		     ->setAttribute('type', 'xpath');
	}
	protected function parseChildren(DOMElement $ir, DOMElement $parent)
	{
		foreach ($parent->childNodes as $child)
		{
			switch ($child->nodeType)
			{
				case \XML_COMMENT_NODE:
					break;
				case \XML_TEXT_NODE:
					if (\trim($child->textContent) !== '')
						$this->appendLiteralOutput($ir, $child->textContent);
					break;
				case \XML_ELEMENT_NODE:
					$this->parseNode($ir, $child);
					break;
				default:
					throw new RuntimeException("Cannot parse node '" . $child->nodeName . "''");
			}
		}
	}
	protected function parseNode(DOMElement $ir, DOMElement $node)
	{
		if ($node->namespaceURI === self::XMLNS_XSL)
		{
			$methodName = 'parseXsl' . \str_replace(' ', '', \ucwords(\str_replace('-', ' ', $node->localName)));
			if (!\method_exists($this, $methodName))
				throw new RuntimeException("Element '" . $node->nodeName . "' is not supported");
			return $this->$methodName($ir, $node);
		}
		$element = $this->appendElement($ir, 'element');
		$element->setAttribute('name', $node->nodeName);
		$xpath = new DOMXPath($node->ownerDocument);
		foreach ($xpath->query('namespace::*', $node) as $ns)
			if ($node->hasAttribute($ns->nodeName))
			{
				$irAttribute = $this->appendElement($element, 'attribute');
				$irAttribute->setAttribute('name', $ns->nodeName);
				$this->appendLiteralOutput($irAttribute, $ns->nodeValue);
			}
		foreach ($node->attributes as $attribute)
		{
			$irAttribute = $this->appendElement($element, 'attribute');
			$irAttribute->setAttribute('name', $attribute->nodeName);
			$this->appendAVT($irAttribute, $attribute->value);
		}
		$this->parseChildren($element, $node);
	}
	protected function parseXslApplyTemplates(DOMElement $ir, DOMElement $node)
	{
		$applyTemplates = $this->appendElement($ir, 'applyTemplates');
		if ($node->hasAttribute('select'))
			$applyTemplates->setAttribute('select', $node->getAttribute('select'));
	}
	protected function parseXslAttribute(DOMElement $ir, DOMElement $node)
	{
		$attribute = $this->appendElement($ir, 'attribute');
		$attribute->setAttribute('name', $node->getAttribute('name'));
		$this->parseChildren($attribute, $node);
	}
	protected function parseXslChoose(DOMElement $ir, DOMElement $node)
	{
		$switch = $this->appendElement($ir, 'switch');
		foreach ($this->query('./xsl:when', $node) as $when)
		{
			$case = $this->appendElement($switch, 'case');
			$case->setAttribute('test', $when->getAttribute('test'));
			$this->parseChildren($case, $when);
		}
		foreach ($this->query('./xsl:otherwise', $node) as $otherwise)
		{
			$case = $this->appendElement($switch, 'case');
			$this->parseChildren($case, $otherwise);
			break;
		}
	}
	protected function parseXslComment(DOMElement $ir, DOMElement $node)
	{
		$comment = $this->appendElement($ir, 'comment');
		$this->parseChildren($comment, $node);
	}
	protected function parseXslCopyOf(DOMElement $ir, DOMElement $node)
	{
		$expr = $node->getAttribute('select');
		if (\preg_match('#^@[-\\w]+(?:\\s*\\|\\s*@[-\\w]+)*$#', $expr, $m))
			$this->appendConditionalAttributes($ir, $expr);
		elseif ($expr === '@*')
			$this->appendElement($ir, 'copyOfAttributes');
		else
			throw new RuntimeException("Unsupported <xsl:copy-of/> expression '" . $expr . "'");
	}
	protected function parseXslElement(DOMElement $ir, DOMElement $node)
	{
		$element = $this->appendElement($ir, 'element');
		$element->setAttribute('name', $node->getAttribute('name'));
		$this->parseChildren($element, $node);
	}
	protected function parseXslIf(DOMElement $ir, DOMElement $node)
	{
		$switch = $this->appendElement($ir, 'switch');
		$case   = $this->appendElement($switch, 'case');
		$case->setAttribute('test', $node->getAttribute('test'));
		$this->parseChildren($case, $node);
	}
	protected function parseXslText(DOMElement $ir, DOMElement $node)
	{
		$this->appendLiteralOutput($ir, $node->textContent);
		if ($node->getAttribute('disable-output-escaping') === 'yes')
			$ir->lastChild->setAttribute('disable-output-escaping', 'yes');
	}
	protected function parseXslValueOf(DOMElement $ir, DOMElement $node)
	{
		$this->appendXPathOutput($ir, $node->getAttribute('select'));
		if ($node->getAttribute('disable-output-escaping') === 'yes')
			$ir->lastChild->setAttribute('disable-output-escaping', 'yes');
	}
}