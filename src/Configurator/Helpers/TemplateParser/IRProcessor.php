<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
abstract class IRProcessor
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	protected $xpath;
	protected function appendElement(DOMElement $parentNode, $name, $value = '')
	{
		return $parentNode->appendChild($parentNode->ownerDocument->createElement($name, $value));
	}
	protected function createXPath(DOMDocument $dom)
	{
		$this->xpath = new DOMXPath($dom);
	}
	protected function evaluate($expr, DOMNode $node = \null)
	{
		return (isset($node)) ? $this->xpath->evaluate($expr, $node) : $this->xpath->evaluate($expr);
	}
	protected function query($query, DOMNode $node = \null)
	{
		return (isset($node)) ? $this->xpath->query($query, $node) : $this->xpath->query($query);
	}
}