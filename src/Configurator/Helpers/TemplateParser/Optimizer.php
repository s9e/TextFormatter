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

class Optimizer extends IRProcessor
{
	/**
	* Optimize an IR
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	public function optimize(DOMDocument $ir)
	{
		$this->createXPath($ir);

		// Get a snapshot of current internal representation
		$xml = $ir->saveXML();

		// Set a maximum number of loops to ward against infinite loops
		$remainingLoops = 10;

		// From now on, keep looping until no further modifications are applied
		do
		{
			$old = $xml;
			$this->optimizeCloseTagElements();
			$xml = $ir->saveXML();
		}
		while (--$remainingLoops > 0 && $xml !== $old);

		$this->removeCloseTagSiblings();
		$this->removeContentFromVoidElements();
		$this->mergeConsecutiveLiteralOutputElements();
		$this->removeEmptyDefaultCases();
	}

	/**
	* Clone closeTag elements that follow a switch into said switch
	*
	* If there's a <closeTag/> right after a <switch/>, clone the <closeTag/> at the end of
	* the every <case/> that does not end with a <closeTag/>
	*
	* @return void
	*/
	protected function cloneCloseTagElementsIntoSwitch()
	{
		$query = '//switch[name(following-sibling::*[1]) = "closeTag"]';
		foreach ($this->query($query) as $switch)
		{
			$closeTag = $switch->nextSibling;
			foreach ($this->query('case', $switch) as $case)
			{
				if (!$case->lastChild || $case->lastChild->nodeName !== 'closeTag')
				{
					$case->appendChild($closeTag->cloneNode());
				}
			}
		}
	}

	/**
	* Clone closeTag elements from the head of a switch's cases before said switch
	*
	* If there's a <closeTag/> at the beginning of every <case/>, clone it and insert it
	* right before the <switch/> unless there's already one
	*
	* @return void
	*/
	protected function cloneCloseTagElementsOutOfSwitch()
	{
		$query = '//switch[case/closeTag][not(case[name(*[1]) != "closeTag"])]';
		foreach ($this->query($query) as $switch)
		{
			$case = $this->query('case/closeTag', $switch)->item(0);
			$switch->parentNode->insertBefore($case->cloneNode(), $switch);
		}
	}

	/**
	* Merge consecutive literal outputs
	*
	* @return void
	*/
	protected function mergeConsecutiveLiteralOutputElements()
	{
		foreach ($this->query('//output[@type="literal"]') as $output)
		{
			$disableOutputEscaping = $output->getAttribute('disable-output-escaping');
			while ($this->nextSiblingIsLiteralOutput($output, $disableOutputEscaping))
			{
				$output->nodeValue = htmlspecialchars($output->nodeValue . $output->nextSibling->nodeValue, ENT_COMPAT);
				$output->parentNode->removeChild($output->nextSibling);
			}
		}
	}

	/**
	* Test whether the next sibling of an element is a literal output element with matching escaping
	*
	* @param  DOMElement $node
	* @param  string     $disableOutputEscaping
	* @return bool
	*/
	protected function nextSiblingIsLiteralOutput(DOMElement $node, $disableOutputEscaping)
	{
		return isset($node->nextSibling) && $node->nextSibling->nodeName === 'output' && $node->nextSibling->getAttribute('type') === 'literal' && $node->nextSibling->getAttribute('disable-output-escaping') === $disableOutputEscaping;
	}

	/**
	* Optimize closeTags elements
	*
	* @return void
	*/
	protected function optimizeCloseTagElements()
	{
		$this->cloneCloseTagElementsIntoSwitch();
		$this->cloneCloseTagElementsOutOfSwitch();
		$this->removeRedundantCloseTagElementsInSwitch();
		$this->removeRedundantCloseTagElements();
	}

	/**
	* Remove redundant closeTag siblings after a switch
	*
	* If all branches of a switch have a closeTag we can remove any closeTag siblings of the switch
	*
	* @return void
	*/
	protected function removeCloseTagSiblings()
	{
		$query = '//switch[not(case[not(closeTag)])]/following-sibling::closeTag';
		$this->removeNodes($query);
	}

	/**
	* Remove content from void elements
	*
	* For each void element, we find whichever <closeTag/> elements close it and remove everything
	* after
	*
	* @return void
	*/
	protected function removeContentFromVoidElements()
	{
		foreach ($this->query('//element[@void="yes"]') as $element)
		{
			$id    = $element->getAttribute('id');
			$query = './/closeTag[@id="' . $id . '"]/following-sibling::*';

			$this->removeNodes($query, $element);
		}
	}

	/**
	* Remove empty default cases (no test and no descendants)
	*
	* @return void
	*/
	protected function removeEmptyDefaultCases()
	{
		$query = '//case[not(@test)][not(*)][. = ""]';
		$this->removeNodes($query);
	}

	/**
	* Remove all nodes that match given XPath query
	*
	* @param  string  $query
	* @param  DOMNode $contextNode
	* @return void
	*/
	protected function removeNodes($query, DOMNode $contextNode = null)
	{
		foreach ($this->query($query, $contextNode) as $node)
		{
			if ($node->parentNode instanceof DOMElement)
			{
				$node->parentNode->removeChild($node);
			}
		}
	}

	/**
	* Remove redundant closeTag elements from the tail of a switch's cases
	*
	* For each <closeTag/> remove duplicate <closeTag/> nodes that are either siblings or
	* descendants of a sibling
	*
	* @return void
	*/
	protected function removeRedundantCloseTagElements()
	{
		foreach ($this->query('//closeTag') as $closeTag)
		{
			$id    = $closeTag->getAttribute('id');
			$query = 'following-sibling::*/descendant-or-self::closeTag[@id="' . $id . '"]';

			$this->removeNodes($query, $closeTag);
		}
	}

	/**
	* Remove redundant closeTag elements from the tail of a switch's cases
	*
	* If there's a <closeTag/> right after a <switch/>, remove all <closeTag/> nodes at the
	* end of every <case/>
	*
	* @return void
	*/
	protected function removeRedundantCloseTagElementsInSwitch()
	{
		$query = '//switch[name(following-sibling::*[1]) = "closeTag"]';
		foreach ($this->query($query) as $switch)
		{
			foreach ($this->query('case', $switch) as $case)
			{
				while ($case->lastChild && $case->lastChild->nodeName === 'closeTag')
				{
					$case->removeChild($case->lastChild);
				}
			}
		}
	}
}