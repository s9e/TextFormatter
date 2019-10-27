<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class OptimizeChoose extends AbstractChooseOptimization
{
	protected function adoptChildren(DOMElement $branch)
	{
		while ($branch->firstChild->firstChild)
			$branch->appendChild($branch->firstChild->removeChild($branch->firstChild->firstChild));
		$branch->removeChild($branch->firstChild);
	}
	protected function matchBranches($childType)
	{
		$branches = $this->getBranches();
		if (!isset($branches[0]->$childType))
			return \false;
		$childNode = $branches[0]->$childType;
		foreach ($branches as $branch)
			if (!isset($branch->$childType) || !$this->isEqualNode($childNode, $branch->$childType))
				return \false;
		return \true;
	}
	protected function matchOnlyChild()
	{
		$branches = $this->getBranches();
		if (!isset($branches[0]->firstChild))
			return \false;
		$firstChild = $branches[0]->firstChild;
		if ($this->isXsl($firstChild, 'choose'))
			return \false;
		foreach ($branches as $branch)
		{
			if ($branch->childNodes->length !== 1 || !($branch->firstChild instanceof DOMElement))
				return \false;
			if (!$this->isEqualTag($firstChild, $branch->firstChild))
				return \false;
		}
		return \true;
	}
	protected function moveFirstChildBefore()
	{
		$branches = $this->getBranches();
		$this->choose->parentNode->insertBefore(\array_pop($branches)->firstChild, $this->choose);
		foreach ($branches as $branch)
			$branch->removeChild($branch->firstChild);
	}
	protected function moveLastChildAfter()
	{
		$branches = $this->getBranches();
		$node     = \array_pop($branches)->lastChild;
		if (isset($this->choose->nextSibling))
			$this->choose->parentNode->insertBefore($node, $this->choose->nextSibling);
		else
			$this->choose->parentNode->appendChild($node);
		foreach ($branches as $branch)
			$branch->removeChild($branch->lastChild);
	}
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
			$this->choose->parentNode->removeChild($this->choose);
		else
			$this->optimizeSingleBranch();
	}
	protected function optimizeCommonFirstChild()
	{
		while ($this->matchBranches('firstChild'))
			$this->moveFirstChildBefore();
	}
	protected function optimizeCommonLastChild()
	{
		while ($this->matchBranches('lastChild'))
			$this->moveLastChildAfter();
	}
	protected function optimizeCommonOnlyChild()
	{
		while ($this->matchOnlyChild())
			$this->reparentChild();
	}
	protected function optimizeEmptyBranch()
	{
		$query = 'count(xsl:when) = 1 and count(xsl:when/node()) = 0 and xsl:otherwise';
		if (!$this->xpath->evaluate($query, $this->choose))
			return;
		list($when) = $this->xpath('xsl:when', $this->choose);
		$when->setAttribute('test', 'not(' . $when->getAttribute('test') . ')');
		$otherwise = $this->xpath('xsl:otherwise', $this->choose)array(0);
		while ($otherwise->firstChild)
			$when->appendChild($otherwise->removeChild($otherwise->firstChild));
	}
	protected function optimizeEmptyOtherwise()
	{
		$query = 'xsl:otherwise[count(node()) = 0]';
		foreach ($this->xpath($query, $this->choose) as $otherwise)
			$this->choose->removeChild($otherwise);
	}
	protected function optimizeSingleBranch()
	{
		$query = 'count(xsl:when) = 1 and not(xsl:otherwise)';
		if (!$this->xpath->evaluate($query, $this->choose))
			return;
		list($when) = $this->xpath('xsl:when', $this->choose);
		$if   = $this->createElement('xsl:if');
		$if->setAttribute('test', $when->getAttribute('test'));
		while ($when->firstChild)
			$if->appendChild($when->removeChild($when->firstChild));
		$this->choose->parentNode->replaceChild($if, $this->choose);
	}
	protected function reparentChild()
	{
		$branches  = $this->getBranches();
		$childNode = $branches[0]->firstChild->cloneNode();
		$childNode->appendChild($this->choose->parentNode->replaceChild($childNode, $this->choose));
		foreach ($branches as $branch)
			$this->adoptChildren($branch);
	}
}