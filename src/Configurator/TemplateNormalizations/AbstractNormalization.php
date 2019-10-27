<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	protected $ownerDocument;
	protected $queries = array();
	protected $xpath;
	public function normalize(DOMElement $template)
	{
		$this->ownerDocument = $template->ownerDocument;
		$this->xpath         = new DOMXPath($this->ownerDocument);
		foreach ($this->getNodes() as $node)
			$this->normalizeNode($node);
		$this->reset();
	}
	protected function createElement($nodeName, $textContent = '')
	{
		$methodName = 'createElement';
		$args       = array($nodeName);
		if ($textContent !== '')
			$args[] = \htmlspecialchars($textContent, \ENT_NOQUOTES, 'UTF-8');
		$prefix = \strstr($nodeName, ':', \true);
		if ($prefix > '')
		{
			$methodName .= 'NS';
			\array_unshift($args, $this->ownerDocument->lookupNamespaceURI($prefix));
		}
		return \call_user_func_array(array($this->ownerDocument, $methodName), $args);
	}
	protected function createText($content)
	{
		return (\trim($content) === '')
		     ? $this->createElement('xsl:text', $content)
		     : $this->ownerDocument->createTextNode($content);
	}
	protected function createTextNode($content)
	{
		return $this->ownerDocument->createTextNode($content);
	}
	protected function getNodes()
	{
		$query = \implode(' | ', $this->queries);
		return ($query === '') ? array() : $this->xpath($query);
	}
	protected function isXsl(DOMNode $node, $localName = \null)
	{
		return ($node->namespaceURI === self::XMLNS_XSL && (!isset($localName) || $localName === $node->localName));
	}
	protected function lowercase($str)
	{
		return \strtr($str, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
	}
	protected function normalizeAttribute(DOMAttr $attribute)
	{
	}
	protected function normalizeElement(DOMElement $element)
	{
	}
	protected function normalizeNode(DOMNode $node)
	{
		if (!$node->parentNode)
			return;
		if ($node instanceof DOMElement)
			$this->normalizeElement($node);
		elseif ($node instanceof DOMAttr)
			$this->normalizeAttribute($node);
	}
	protected function reset()
	{
		$this->ownerDocument = \null;
		$this->xpath         = \null;
	}
	protected function xpath($query, DOMNode $node = \null)
	{
		$query = \str_replace('$XSL', '"' . self::XMLNS_XSL . '"', $query);
		return \iterator_to_array($this->xpath->query($query, $node));
	}
}