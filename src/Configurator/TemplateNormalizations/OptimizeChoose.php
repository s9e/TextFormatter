<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

class OptimizeChoose extends AbstractChooseOptimization
{
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
			if (!$childNode->isEqualNode($branch->$childType))
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
			if ($branch->childNodes->length !== 1 || !($branch->firstChild instanceof Element))
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
		$this->choose->before(array_pop($branches)->firstChild);
		foreach ($branches as $branch)
		{
			$branch->firstChild->remove();
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
		$this->choose->after(array_pop($branches)->lastChild);
		foreach ($branches as $branch)
		{
			$branch->lastChild->remove();
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
			$this->choose->remove();
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
		if (!$this->choose->evaluate($query))
		{
			return;
		}

		// test="@foo" becomes test="not(@foo)"
		$when = $this->choose->firstOf('xsl:when');
		$when->setAttribute('test', 'not(' . $when->getAttribute('test') . ')');

		$otherwise = $this->choose->firstOf('xsl:otherwise');
		$when->append(...$otherwise->childNodes);
	}

	/**
	* Optimize away the xsl:otherwise child of current xsl:choose if it's empty
	*
	* @return void
	*/
	protected function optimizeEmptyOtherwise()
	{
		$query = 'xsl:otherwise[count(node()) = 0]';
		foreach ($this->choose->query($query) as $otherwise)
		{
			$otherwise->remove();
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
		if (!$this->choose->evaluate($query))
		{
			return;
		}
		$when = $this->choose->firstOf('xsl:when');
		$if   = $this->choose->replaceWithXslIf($when->getAttribute('test'));
		$if->append(...$when->childNodes);
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
		$outerNode = $branches[0]->firstChild->cloneNode();
		foreach ($branches as $branch)
		{
			$branch->append(...$branch->firstChild->childNodes);
			$branch->firstChild->remove();
		}
		$this->choose->replaceWith($outerNode);
		$outerNode->appendChild($this->choose);
	}
}