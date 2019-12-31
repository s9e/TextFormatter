<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
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
	/**
	* @var Normalizer
	*/
	protected $normalizer;

	/**
	* @param  Normalizer $normalizer
	* @return void
	*/
	public function __construct(Normalizer $normalizer)
	{
		$this->normalizer = $normalizer;
	}

	/**
	* Parse a template into an internal representation
	*
	* @param  string      $template Source template
	* @return DOMDocument           Internal representation
	*/
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

	/**
	* Append <output/> elements corresponding to given AVT
	*
	* @param  DOMElement $parentNode Parent node
	* @param  string     $avt        Attribute value template
	* @return void
	*/
	protected function appendAVT(DOMElement $parentNode, $avt)
	{
		foreach (AVTHelper::parse($avt) as $token)
		{
			if ($token[0] === 'expression')
			{
				$this->appendXPathOutput($parentNode, $token[1]);
			}
			else
			{
				$this->appendLiteralOutput($parentNode, $token[1]);
			}
		}
	}

	/**
	* Append an <output/> element with literal content to given node
	*
	* @param  DOMElement $parentNode Parent node
	* @param  string     $content    Content to output
	* @return void
	*/
	protected function appendLiteralOutput(DOMElement $parentNode, $content)
	{
		if ($content === '')
		{
			return;
		}

		$this->appendElement($parentNode, 'output', htmlspecialchars($content))
		     ->setAttribute('type', 'literal');
	}

	/**
	* Append the structure for a <xsl:copy-of/> element to given node
	*
	* @param  DOMElement $parentNode Parent node
	* @param  string     $expr       Select expression, which is should only contain attributes
	* @return void
	*/
	protected function appendConditionalAttributes(DOMElement $parentNode, $expr)
	{
		preg_match_all('(@([-\\w]+))', $expr, $matches);
		foreach ($matches[1] as $attrName)
		{
			// Create a switch element in the IR
			$switch = $this->appendElement($parentNode, 'switch');
			$case   = $this->appendElement($switch, 'case');
			$case->setAttribute('test', '@' . $attrName);

			// Append an attribute element
			$attribute = $this->appendElement($case, 'attribute');
			$attribute->setAttribute('name', $attrName);

			// Set the attribute's content, which is simply the copied attribute's value
			$this->appendXPathOutput($attribute, '@' . $attrName);
		}
	}

	/**
	* Append an <output/> element for given XPath expression to given node
	*
	* @param  DOMElement $parentNode Parent node
	* @param  string     $expr       XPath expression
	* @return void
	*/
	protected function appendXPathOutput(DOMElement $parentNode, $expr)
	{
		$this->appendElement($parentNode, 'output', htmlspecialchars(trim($expr)))
		     ->setAttribute('type', 'xpath');
	}

	/**
	* Parse all the children of a given element
	*
	* @param  DOMElement $ir     Node in the internal representation that represents the parent node
	* @param  DOMElement $parent Parent node
	* @return void
	*/
	protected function parseChildren(DOMElement $ir, DOMElement $parent)
	{
		foreach ($parent->childNodes as $child)
		{
			switch ($child->nodeType)
			{
				case XML_COMMENT_NODE:
					// Do nothing
					break;

				case XML_TEXT_NODE:
					if (trim($child->textContent) !== '')
					{
						$this->appendLiteralOutput($ir, $child->textContent);
					}
					break;

				case XML_ELEMENT_NODE:
					$this->parseNode($ir, $child);
					break;

				default:
					throw new RuntimeException("Cannot parse node '" . $child->nodeName . "''");
			}
		}
	}

	/**
	* Parse a given node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node Node to parse
	* @return void
	*/
	protected function parseNode(DOMElement $ir, DOMElement $node)
	{
		// XSL elements are parsed by the corresponding parseXsl* method
		if ($node->namespaceURI === self::XMLNS_XSL)
		{
			$methodName = 'parseXsl' . str_replace(' ', '', ucwords(str_replace('-', ' ', $node->localName)));
			if (!method_exists($this, $methodName))
			{
				throw new RuntimeException("Element '" . $node->nodeName . "' is not supported");
			}

			return $this->$methodName($ir, $node);
		}

		// Create an <element/> with a name attribute equal to given node's name
		$element = $this->appendElement($ir, 'element');
		$element->setAttribute('name', $node->nodeName);

		// Append an <attribute/> element for each namespace declaration
		$xpath = new DOMXPath($node->ownerDocument);
		foreach ($xpath->query('namespace::*', $node) as $ns)
		{
			if ($node->hasAttribute($ns->nodeName))
			{
				$irAttribute = $this->appendElement($element, 'attribute');
				$irAttribute->setAttribute('name', $ns->nodeName);
				$this->appendLiteralOutput($irAttribute, $ns->nodeValue);
			}
		}

		// Append an <attribute/> element for each of this node's attribute
		foreach ($node->attributes as $attribute)
		{
			$irAttribute = $this->appendElement($element, 'attribute');
			$irAttribute->setAttribute('name', $attribute->nodeName);

			// Append an <output/> element to represent the attribute's value
			$this->appendAVT($irAttribute, $attribute->value);
		}

		// Parse the content of this node
		$this->parseChildren($element, $node);
	}

	/**
	* Parse an <xsl:apply-templates/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:apply-templates/> node
	* @return void
	*/
	protected function parseXslApplyTemplates(DOMElement $ir, DOMElement $node)
	{
		$applyTemplates = $this->appendElement($ir, 'applyTemplates');
		if ($node->hasAttribute('select'))
		{
			$applyTemplates->setAttribute('select', $node->getAttribute('select'));
		}
	}

	/**
	* Parse an <xsl:attribute/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:attribute/> node
	* @return void
	*/
	protected function parseXslAttribute(DOMElement $ir, DOMElement $node)
	{
		$attribute = $this->appendElement($ir, 'attribute');
		$attribute->setAttribute('name', $node->getAttribute('name'));
		$this->parseChildren($attribute, $node);
	}

	/**
	* Parse an <xsl:choose/> node and its <xsl:when/> and <xsl:otherwise/> children into the
	* internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:choose/> node
	* @return void
	*/
	protected function parseXslChoose(DOMElement $ir, DOMElement $node)
	{
		$switch = $this->appendElement($ir, 'switch');
		foreach ($this->query('./xsl:when', $node) as $when)
		{
			// Create a <case/> element with the original test condition in @test
			$case = $this->appendElement($switch, 'case');
			$case->setAttribute('test', $when->getAttribute('test'));
			$this->parseChildren($case, $when);
		}

		// Add the default branch, which is presumed to be last
		foreach ($this->query('./xsl:otherwise', $node) as $otherwise)
		{
			$case = $this->appendElement($switch, 'case');
			$this->parseChildren($case, $otherwise);

			// There should be only one <xsl:otherwise/> but we'll break anyway
			break;
		}
	}

	/**
	* Parse an <xsl:comment/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:comment/> node
	* @return void
	*/
	protected function parseXslComment(DOMElement $ir, DOMElement $node)
	{
		$comment = $this->appendElement($ir, 'comment');
		$this->parseChildren($comment, $node);
	}

	/**
	* Parse an <xsl:copy-of/> node into the internal representation
	*
	* NOTE: only attributes are supported
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:copy-of/> node
	* @return void
	*/
	protected function parseXslCopyOf(DOMElement $ir, DOMElement $node)
	{
		$expr = $node->getAttribute('select');
		if (preg_match('#^@[-\\w]+(?:\\s*\\|\\s*@[-\\w]+)*$#', $expr, $m))
		{
			// <xsl:copy-of select="@foo"/>
			$this->appendConditionalAttributes($ir, $expr);
		}
		elseif ($expr === '@*')
		{
			// <xsl:copy-of select="@*"/>
			$this->appendElement($ir, 'copyOfAttributes');
		}
		else
		{
			throw new RuntimeException("Unsupported <xsl:copy-of/> expression '" . $expr . "'");
		}
	}

	/**
	* Parse an <xsl:element/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:element/> node
	* @return void
	*/
	protected function parseXslElement(DOMElement $ir, DOMElement $node)
	{
		$element = $this->appendElement($ir, 'element');
		$element->setAttribute('name', $node->getAttribute('name'));
		$this->parseChildren($element, $node);
	}

	/**
	* Parse an <xsl:if/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:if/> node
	* @return void
	*/
	protected function parseXslIf(DOMElement $ir, DOMElement $node)
	{
		// An <xsl:if/> is represented by a <switch/> with only one <case/>
		$switch = $this->appendElement($ir, 'switch');
		$case   = $this->appendElement($switch, 'case');
		$case->setAttribute('test', $node->getAttribute('test'));

		// Parse this branch's content
		$this->parseChildren($case, $node);
	}

	/**
	* Parse an <xsl:text/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:text/> node
	* @return void
	*/
	protected function parseXslText(DOMElement $ir, DOMElement $node)
	{
		$this->appendLiteralOutput($ir, $node->textContent);
		if ($node->getAttribute('disable-output-escaping') === 'yes')
		{
			$ir->lastChild->setAttribute('disable-output-escaping', 'yes');
		}
	}

	/**
	* Parse an <xsl:value-of/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:value-of/> node
	* @return void
	*/
	protected function parseXslValueOf(DOMElement $ir, DOMElement $node)
	{
		$this->appendXPathOutput($ir, $node->getAttribute('select'));
		if ($node->getAttribute('disable-output-escaping') === 'yes')
		{
			$ir->lastChild->setAttribute('disable-output-escaping', 'yes');
		}
	}
}