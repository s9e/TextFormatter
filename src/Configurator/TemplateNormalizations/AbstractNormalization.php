<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMComment;
use DOMElement;
use DOMNode;
use DOMXPath;

abstract class AbstractNormalization
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var DOMDocument Document that holds the template being normalized
	*/
	protected $ownerDocument;

	/**
	* @var string[] XPath queries used to retrieve nodes of interest
	*/
	protected $queries = [];

	/**
	* @var DOMXPath
	*/
	protected $xpath;

	/**
	* Apply this normalization rule to given template
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$this->ownerDocument = $template->ownerDocument;
		$this->xpath         = new DOMXPath($this->ownerDocument);
		$this->xpath->registerNamespace('xsl', self::XMLNS_XSL);
		foreach ($this->getNodes() as $node)
		{
			$this->normalizeNode($node);
		}
		$this->reset();
	}

	/**
	* Create an element in current template
	*
	* @param  string     $nodeName
	* @param  string     $textContent
	* @return DOMElement
	*/
	protected function createElement($nodeName, $textContent = '')
	{
		$methodName = 'createElement';
		$args       = [$nodeName];

		// Add the text content for the new element
		if ($textContent !== '')
		{
			$args[] = htmlspecialchars($textContent, ENT_NOQUOTES, 'UTF-8');
		}

		// Handle namespaced elements
		$prefix = strstr($nodeName, ':', true);
		if ($prefix > '')
		{
			$methodName .= 'NS';
			array_unshift($args, $this->ownerDocument->lookupNamespaceURI($prefix));
		}

		return call_user_func_array([$this->ownerDocument, $methodName], $args);
	}

	/**
	* Create an xsl:text element or a text node in current template
	*
	* @param  string  $content
	* @return DOMNode
	*/
	protected function createText($content)
	{
		return (trim($content) === '')
		     ? $this->createElement('xsl:text', $content)
		     : $this->ownerDocument->createTextNode($content);
	}

	/**
	* Create a text node in current template
	*
	* @param  string  $content
	* @return DOMText
	*/
	protected function createTextNode($content)
	{
		return $this->ownerDocument->createTextNode($content);
	}

	/**
	* Query and return a list of nodes of interest
	*
	* @return DOMNode[]
	*/
	protected function getNodes()
	{
		$query = implode(' | ', $this->queries);

		return ($query === '') ? [] : $this->xpath($query);
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
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function normalizeElement(DOMElement $element)
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
		if (!$node->parentNode)
		{
			// Ignore nodes that have been removed from the document
			return;
		}
		if ($node instanceof DOMElement)
		{
			$this->normalizeElement($node);
		}
		elseif ($node instanceof DOMAttr)
		{
			$this->normalizeAttribute($node);
		}
	}

	/**
	* Reset this instance's properties after usage
	*
	* @return void
	*/
	protected function reset()
	{
		$this->ownerDocument = null;
		$this->xpath         = null;
	}

	/**
	* Evaluate given XPath expression
	*
	* For convenience, $XSL is replaced with the XSL namespace URI as a string
	*
	* @param  string    $query XPath query
	* @param  DOMNode   $node  Context node
	* @return DOMNode[]
	*/
	protected function xpath($query, DOMNode $node = null)
	{
		$query = str_replace('$XSL', '"' . self::XMLNS_XSL . '"', $query);

		return iterator_to_array($this->xpath->query($query, $node));
	}
}