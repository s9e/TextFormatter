<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMComment;
use DOMNode;
use DOMText;
use s9e\SweetDOM\Document;
use s9e\SweetDOM\Element;

abstract class AbstractNormalization
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var Document Document that holds the template being normalized
	*/
	protected Document $ownerDocument;

	/**
	* @var string[] XPath queries used to retrieve nodes of interest
	*/
	protected array $queries = [];

	/**
	* Apply this normalization rule to given template
	*
	* @param Element $template <xsl:template/> node
	*/
	public function normalize(Element $template): void
	{
		$this->ownerDocument = $template->ownerDocument;
		foreach ($this->getNodes() as $node)
		{
			// Ignore nodes that have been removed from the document
			if ($node->parentNode)
			{
				$this->normalizeNode($node);
			}
		}
		$this->reset();
	}

	/**
	* Create an xsl:text element or a text node in current template
	*
	* @param  string  $content
	* @return DOMNode
	*/
	protected function createText(string $content): DOMNode
	{
		return (trim($content) === '')
		     ? $this->ownerDocument->createXslText($content)
		     : $this->ownerDocument->createTextNode($content);
	}

	/**
	* Create a text node in current template
	*
	* @param  string  $content
	* @return DOMText
	*/
	protected function createTextNode($content): DOMText
	{
		return $this->ownerDocument->createTextNode($content);
	}

	/**
	* Query and return a list of nodes of interest
	*
	* @return DOMNode[]
	*/
	protected function getNodes(): array
	{
		$query = implode(' | ', $this->queries);

		return ($query === '') ? [] : iterator_to_array($this->ownerDocument->query($query));
	}

	/**
	* Test whether given node is an XSL element
	*
	* @param  DOMNode $node
	* @param  string  $localName
	* @return bool
	*/
	protected function isXsl(DOMNode $node, $localName = null)
	{
		return ($node->namespaceURI === self::XMLNS_XSL && (!isset($localName) || $localName === $node->localName));
	}

	/**
	* Make an ASCII string lowercase
	*
	* @param  string $str Original string
	* @return string      Lowercased string
	*/
	protected function lowercase($str)
	{
		return strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}

	/**
	* Normalize given attribute
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
	}

	/**
	* Normalize given element
	*/
	protected function normalizeElement(Element $element): void
	{
	}

	/**
	* Normalize given node
	*
	* @param  DOMNode $node
	* @return void
	*/
	protected function normalizeNode(DOMNode $node)
	{
		if ($node instanceof Element)
		{
			$this->normalizeElement($node);
		}
		elseif ($node instanceof DOMAttr)
		{
			$this->normalizeAttribute($node);
		}
		elseif ($node instanceof DOMText)
		{
			$this->normalizeText($node);
		}
	}

	/**
	* Normalize given text node
	*/
	protected function normalizeText(DOMText $node): void
	{
	}

	/**
	* Reset this instance's properties after usage
	*
	* @return void
	*/
	protected function reset()
	{
		unset($this->ownerDocument);
	}
}