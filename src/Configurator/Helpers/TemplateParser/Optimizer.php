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
class Optimizer extends IRProcessor
{
	public function optimize(DOMDocument $ir)
	{
		$this->createXPath($ir);
		$xml = $ir->saveXML();
		$remainingLoops = 10;
		do
		{
			$old = $xml;
			$this->optimizeCloseTagElements($ir);
			$xml = $ir->saveXML();
		}
		while (--$remainingLoops > 0 && $xml !== $old);
		$this->removeCloseTagSiblings($ir);
		$this->removeContentFromVoidElements($ir);
		$this->mergeConsecutiveLiteralOutputElements($ir);
		$this->removeEmptyDefaultCases($ir);
	}
	protected function cloneCloseTagElementsIntoSwitch(DOMDocument $ir)
	{
		$query = '//switch[name(following-sibling::*[1]) = "closeTag"]';
		foreach ($this->query($query) as $switch)
		{
			$closeTag = $switch->nextSibling;
			foreach ($this->query('case', $switch) as $case)
				if (!$case->lastChild || $case->lastChild->nodeName !== 'closeTag')
					$case->appendChild($closeTag->cloneNode());
		}
	}
	protected function cloneCloseTagElementsOutOfSwitch(DOMDocument $ir)
	{
		$query = '//switch[case/closeTag][not(case[name(*[1]) != "closeTag"])]';
		foreach ($this->query($query) as $switch)
		{
			$case = $this->query('case/closeTag', $switch)->item(0);
			$switch->parentNode->insertBefore($case->cloneNode(), $switch);
		}
	}
	protected function mergeConsecutiveLiteralOutputElements(DOMDocument $ir)
	{
		foreach ($this->query('//output[@type="literal"]') as $output)
		{
			$disableOutputEscaping = $output->getAttribute('disable-output-escaping');
			while ($this->nextSiblingIsLiteralOutput($output, $disableOutputEscaping))
			{
				$output->nodeValue = \htmlspecialchars($output->nodeValue . $output->nextSibling->nodeValue);
				$output->parentNode->removeChild($output->nextSibling);
			}
		}
	}
	protected function nextSiblingIsLiteralOutput(DOMElement $node, $disableOutputEscaping)
	{
		return isset($node->nextSibling) && $node->nextSibling->nodeName === 'output' && $node->nextSibling->getAttribute('type') === 'literal' && $node->nextSibling->getAttribute('disable-output-escaping') === $disableOutputEscaping;
	}
	protected function optimizeCloseTagElements(DOMDocument $ir)
	{
		$this->cloneCloseTagElementsIntoSwitch($ir);
		$this->cloneCloseTagElementsOutOfSwitch($ir);
		$this->removeRedundantCloseTagElementsInSwitch($ir);
		$this->removeRedundantCloseTagElements($ir);
	}
	protected function removeCloseTagSiblings(DOMDocument $ir)
	{
		$query = '//switch[not(case[not(closeTag)])]/following-sibling::closeTag';
		$this->removeNodes($ir, $query);
	}
	protected function removeContentFromVoidElements(DOMDocument $ir)
	{
		foreach ($this->query('//element[@void="yes"]') as $element)
		{
			$id    = $element->getAttribute('id');
			$query = './/closeTag[@id="' . $id . '"]/following-sibling::*';
			$this->removeNodes($ir, $query, $element);
		}
	}
	protected function removeEmptyDefaultCases(DOMDocument $ir)
	{
		$query = '//case[not(@test)][not(*)][. = ""]';
		$this->removeNodes($ir, $query);
	}
	protected function removeNodes(DOMDocument $ir, $query, DOMNode $contextNode = \null)
	{
		foreach ($this->query($query, $contextNode) as $node)
			if ($node->parentNode instanceof DOMElement)
				$node->parentNode->removeChild($node);
	}
	protected function removeRedundantCloseTagElements(DOMDocument $ir)
	{
		foreach ($this->query('//closeTag') as $closeTag)
		{
			$id    = $closeTag->getAttribute('id');
			$query = 'following-sibling::*/descendant-or-self::closeTag[@id="' . $id . '"]';
			$this->removeNodes($ir, $query, $closeTag);
		}
	}
	protected function removeRedundantCloseTagElementsInSwitch(DOMDocument $ir)
	{
		$query = '//switch[name(following-sibling::*[1]) = "closeTag"]';
		foreach ($this->query($query) as $switch)
			foreach ($this->query('case', $switch) as $case)
				while ($case->lastChild && $case->lastChild->nodeName === 'closeTag')
					$case->removeChild($case->lastChild);
	}
}