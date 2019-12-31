<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;

/**
* Merge xsl:when branches if they have identical content
*
* NOTE: may fail if branches have identical equality expressions, e.g. "@a=1" and "@a=1"
*/
class MergeIdenticalConditionalBranches extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//xsl:choose'];

	/**
	* Collect consecutive xsl:when elements that share the same kind of equality tests
	*
	* Will return xsl:when elements that test a constant part (e.g. a literal) against the same
	* variable part (e.g. the same attribute)
	*
	* @param  DOMNode      $node First node to inspect
	* @return DOMElement[]
	*/
	protected function collectCompatibleBranches(DOMNode $node)
	{
		$nodes  = [];
		$key    = null;
		$values = [];

		while ($node && $this->isXsl($node, 'when'))
		{
			$branch = XPathHelper::parseEqualityExpr($node->getAttribute('test'));

			if ($branch === false || count($branch) !== 1)
			{
				// The expression is not entirely composed of equalities, or they have a different
				// variable part
				break;
			}

			if (isset($key) && key($branch) !== $key)
			{
				// Not the same variable as our branches
				break;
			}

			if (array_intersect($values, end($branch)))
			{
				// Duplicate values across branches, e.g. ".=1 or .=2" and ".=2 or .=3"
				break;
			}

			$key    = key($branch);
			$values = array_merge($values, end($branch));

			// Record this node then move on to the next sibling
			$nodes[] = $node;
			$node    = $node->nextSibling;
		}

		return $nodes;
	}

	/**
	* Merge identical xsl:when elements from a list
	*
	* @param  DOMElement[] $nodes
	* @return void
	*/
	protected function mergeBranches(array $nodes)
	{
		$sortedNodes = [];
		foreach ($nodes as $node)
		{
			$outerXML = $node->ownerDocument->saveXML($node);
			$innerXML = preg_replace('([^>]+>(.*)<[^<]+)s', '$1', $outerXML);

			$sortedNodes[$innerXML][] = $node;
		}

		foreach ($sortedNodes as $identicalNodes)
		{
			if (count($identicalNodes) < 2)
			{
				continue;
			}

			$expr = [];
			foreach ($identicalNodes as $i => $node)
			{
				$expr[] = $node->getAttribute('test');

				if ($i > 0)
				{
					$node->parentNode->removeChild($node);
				}
			}

			$identicalNodes[0]->setAttribute('test', implode(' or ', $expr));
		}
	}

	/**
	* Inspect the branches of an xsl:choose element and merge branches if their content is identical
	* and their order does not matter
	*
	* @param  DOMElement $choose xsl:choose element
	* @return void
	*/
	protected function mergeCompatibleBranches(DOMElement $choose)
	{
		$node = $choose->firstChild;
		while ($node)
		{
			$nodes = $this->collectCompatibleBranches($node);

			if (count($nodes) > 1)
			{
				$node = end($nodes)->nextSibling;

				// Try to merge branches if there's more than one of them
				$this->mergeBranches($nodes);
			}
			else
			{
				$node = $node->nextSibling;
			}
		}
	}

	/**
	* Inspect the branches of an xsl:choose element and merge consecutive branches if their content
	* is identical
	*
	* @param  DOMElement $choose xsl:choose element
	* @return void
	*/
	protected function mergeConsecutiveBranches(DOMElement $choose)
	{
		// Try to merge consecutive branches even if their test conditions are not compatible,
		// e.g. "@a=1" and "@b=2"
		$nodes = [];
		foreach ($choose->childNodes as $node)
		{
			if ($this->isXsl($node, 'when'))
			{
				$nodes[] = $node;
			}
		}

		$i = count($nodes);
		while (--$i > 0)
		{
			$this->mergeBranches([$nodes[$i - 1], $nodes[$i]]);
		}
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$this->mergeCompatibleBranches($element);
		$this->mergeConsecutiveBranches($element);
	}
}