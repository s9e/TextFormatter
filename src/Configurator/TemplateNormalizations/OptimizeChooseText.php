<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
use DOMText;
class OptimizeChooseText extends AbstractChooseOptimization
{
	protected function adjustTextNodes($childType, $pos, $len = \PHP_INT_MAX)
	{
		foreach ($this->getBranches() as $branch)
		{
			$node            = $branch->$childType;
			$node->nodeValue = \substr($node->textContent, $pos, $len);
		}
	}
	protected function getPrefixLength(array $strings)
	{
		$i      = 0;
		$len    = 0;
		$maxLen = \min(\array_map('strlen', $strings));
		while ($i < $maxLen)
		{
			$c = $strings[0][$i];
			foreach ($strings as $string)
				if ($string[$i] !== $c)
					break 2;
			$len = ++$i;
		}
		return $len;
	}
	protected function getTextContent($childType)
	{
		$strings = array();
		foreach ($this->getBranches() as $branch)
		{
			if (!($branch->$childType instanceof DOMText))
				return array();
			$strings[] = $branch->$childType->textContent;
		}
		return $strings;
	}
	protected function optimizeChoose()
	{
		if (!$this->hasOtherwise())
			return;
		$this->optimizeLeadingText();
		$this->optimizeTrailingText();
	}
	protected function optimizeLeadingText()
	{
		$strings = $this->getTextContent('firstChild');
		if (empty($strings))
			return;
		$len = $this->getPrefixLength($strings);
		if ($len)
		{
			$this->adjustTextNodes('firstChild', $len);
			$this->choose->parentNode->insertBefore(
				$this->createText(\substr($strings[0], 0, $len)),
				$this->choose
			);
		}
	}
	protected function optimizeTrailingText()
	{
		$strings = $this->getTextContent('lastChild');
		if (empty($strings))
			return;
		$len = $this->getPrefixLength(\array_map('strrev', $strings));
		if ($len)
		{
			$this->adjustTextNodes('lastChild', 0, -$len);
			$this->choose->parentNode->insertBefore(
				$this->createText(\substr($strings[0], -$len)),
				$this->choose->nextSibling
			);
		}
	}
}