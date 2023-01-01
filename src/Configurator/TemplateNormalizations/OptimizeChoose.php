<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

class OptimizeChoose extends AbstractChooseOptimization
{
	/**
	* Adopt the children of given element's only child
	*
	* @param  DOMElement $branch
	* @return void
	*/
	protected function adoptChildren(DOMElement $branch)
	{
		while ($branch->firstChild->firstChild)
		{
			$branch->appendChild($branch->firstChild->removeChild($branch->firstChild->firstChild));
		}
		$branch->removeChild($branch->firstChild);
	}

	/**
	* Test whether all branches of current xsl:choose element share a common firstChild/lastChild
	*
	* @param  string $childType Either firstChild or lastChild
	* @return bool
	*/
	protected function matchBranches($childType)
	{
		$branches = $this->getBranches();
		if (!isset($branches[0]->$childType))
		{
			return false;
		}

		$childNode = $branches[0]->$childType;
		foreach ($branches as $branch)
		{
			if (!isset($branch->$childType) || !$this->isEqualNode($childNode, $branch->$childType))
			{
				return false;
			}
		}

		return true;
	}

	/**
	* Test whether all branches of current xsl:choose element have a single child with the same start tag
	*
	* @return bool
	*/
	protected function matchOnlyChild()
	{
		$branches = $this->getBranches();
		if (!isset($branches[0]->firstChild))
		{
			return false;
		}

		$firstChild = $branches[0]->firstChild;
		if ($this->isXsl($firstChild, 'choose'))
		{
			// Abort on xsl:choose because we can't move it without moving its children
			return false;
		}

		foreach ($branches as $branch)
		{
			if ($branch->childNodes->length !== 1 || !($branch->firstChild instanceof DOMElement))
			{
				return false;
			}
			if (!$this->isEqualTag($firstChild, $branch->firstChild))
			{
				return false;
			}
		}

		return true;
	}

	/**
	* Move the firstChild of each branch before current xsl:choose
	*
	* @return void
	*/
	protected function moveFirstChildBefore()
	{
		$branches = $this->getBranches();
		$this->choose->parentNode->insertBefore(array_pop($branches)->firstChild, $this->choose);
		foreach ($branches as $branch)
		{
			$branch->removeChild($branch->firstChild);
		}
	}

	/**
	* Move the lastChild of each branch after current xsl:choose
	*
	* @return void
	*/
	protected function moveLastChildAfter()
	{
		$branches = $this->getBranches();
		$node     = array_pop($branches)->lastChild;
		if (isset($this->choose->nextSibling))
		{
			$this->choose->parentNode->insertBefore($node, $this->choose->nextSibling);
		}
		else
		{
			$this->choose->parentNode->appendChild($node);
		}
		foreach ($branches as $branch)
		{
			$branch->removeChild($branch->lastChild);
		}
	}

	/**
	* {@inheritdoc}
	*/
	protected function optimizeChoose()
	{
		if ($this->hasOtherwise())
		{
			$this->optimizeCommonFirstChild();
			$this->optimizeCommonLastChild();
			$this->optimizeCommonOnlyChild();
			$this->optimizeEmptyBranch();
			$this->optimizeEmptyOtherwise();
		}
		if ($this->isEmpty())
		{
			$this->choose->parentNode->removeChild($this->choose);
		}
		else
		{
			$this->optimizeSingleBranch();
		}
	}

	/**
	* Optimize current xsl:choose by moving out the first child of each branch if they match
	*
	* @return void
	*/
	protected function optimizeCommonFirstChild()
	{
		while ($this->matchBranches('firstChild'))
		{
			$this->moveFirstChildBefore();
		}
	}

	/**
	* Optimize current xsl:choose by moving out the last child of each branch if they match
	*
	* @return void
	*/
	protected function optimizeCommonLastChild()
	{
		while ($this->matchBranches('lastChild'))
		{
			$this->moveLastChildAfter();
		}
	}

	/**
	* Optimize current xsl:choose by moving out only child of each branch if they match
	*
	* This will reorder xsl:choose/xsl:when/div into div/xsl:choose/xsl:when if every branch has
	* the same only child (excluding the child's own descendants)
	*
	* @return void
	*/
	protected function optimizeCommonOnlyChild()
	{
		while ($this->matchOnlyChild())
		{
			$this->reparentChild();
		}
	}

	/**
	* Switch the logic of an xsl:otherwise if the only other branch is empty
	*
	* @return void
	*/
	protected function optimizeEmptyBranch()
	{
		$query = 'count(xsl:when) = 1 and count(xsl:when/node()) = 0 and xsl:otherwise';
		if (!$this->xpath->evaluate($query, $this->choose))
		{
			return;
		}

		// test="@foo" becomes test="not(@foo)"
		$when = $this->xpath('xsl:when', $this->choose)[0];
		$when->setAttribute('test', 'not(' . $when->getAttribute('test') . ')');

		$otherwise = $this->xpath('xsl:otherwise', $this->choose)[0];
		while ($otherwise->firstChild)
		{
			$when->appendChild($otherwise->removeChild($otherwise->firstChild));
		}
	}

	/**
	* Optimize away the xsl:otherwise child of current xsl:choose if it's empty
	*
	* @return void
	*/
	protected function optimizeEmptyOtherwise()
	{
		$query = 'xsl:otherwise[count(node()) = 0]';
		foreach ($this->xpath($query, $this->choose) as $otherwise)
		{
			$this->choose->removeChild($otherwise);
		}
	}

	/**
	* Replace current xsl:choose with xsl:if if it has only one branch
	*
	* @return void
	*/
	protected function optimizeSingleBranch()
	{
		$query = 'count(xsl:when) = 1 and not(xsl:otherwise)';
		if (!$this->xpath->evaluate($query, $this->choose))
		{
			return;
		}
		$when = $this->xpath('xsl:when', $this->choose)[0];
		$if   = $this->createElement('xsl:if');
		$if->setAttribute('test', $when->getAttribute('test'));
		while ($when->firstChild)
		{
			$if->appendChild($when->removeChild($when->firstChild));
		}

		$this->choose->parentNode->replaceChild($if, $this->choose);
	}

	/**
	* Reorder the current xsl:choose tree to make it a child of the first child of its first branch
	*
	* This will reorder xsl:choose/xsl:when/div into div/xsl:choose/xsl:when
	*
	* @return void
	*/
	protected function reparentChild()
	{
		$branches  = $this->getBranches();
		$childNode = $branches[0]->firstChild->cloneNode();
		$childNode->appendChild($this->choose->parentNode->replaceChild($childNode, $this->choose));

		foreach ($branches as $branch)
		{
			$this->adoptChildren($branch);
		}
	}
}