<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\TemplateParser;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

abstract class IRProcessor
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var DOMXPath
	*/
	protected $xpath;

	/**
	* Create and append an element to given node in the IR
	*
	* @param  DOMElement $parentNode Parent node of the element
	* @param  string     $name       Tag name of the element
	* @param  string     $value      Value of the element
	* @return DOMElement             The created element
	*/
	protected function appendElement(DOMElement $parentNode, $name, $value = '')
	{
		return $parentNode->appendChild($parentNode->ownerDocument->createElement($name, $value));
	}

	/**
	* Create and store an instance of DOMXPath for given document
	*
	* @param  DOMDocument $dom
	* @return void
	*/
	protected function createXPath(DOMDocument $dom)
	{
		$this->xpath = new DOMXPath($dom);
	}

	/**
	* Evaluate an XPath expression and return its result
	*
	* @param  string  $expr XPath expression
	* @param  DOMNode $node Context node
	* @return mixed
	*/
	protected function evaluate($expr, DOMNode $node = null)
	{
		return (isset($node)) ? $this->xpath->evaluate($expr, $node) : $this->xpath->evaluate($expr);
	}

	/**
	* Run an XPath query and return its result
	*
	* @param  string       $query XPath query
	* @param  DOMNode      $node  Context node
	* @return \DOMNodeList
	*/
	protected function query($query, DOMNode $node = null)
	{
		return (isset($node)) ? $this->xpath->query($query, $node) : $this->xpath->query($query);
	}
}