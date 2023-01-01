<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMText;

class OptimizeChooseText extends AbstractChooseOptimization
{
	/**
	* Adjust length of the text nodes of current xsl:choose element's branches
	*
	* @param  string  $childType Either firstChild or lastChild
	* @param  integer $pos
	* @param  integer $len
	* @return void
	*/
	protected function adjustTextNodes($childType, $pos, $len = PHP_INT_MAX)
	{
		foreach ($this->getBranches() as $branch)
		{
			$node            = $branch->$childType;
			$node->nodeValue = substr($node->textContent, $pos, $len);
		}
	}

	/**
	* Compute the number of leading characters common to all strings
	*
	* @param  string[] $strings
	* @return integer
	*/
	protected function getPrefixLength(array $strings)
	{
		$i      = 0;
		$len    = 0;
		$maxLen = min(array_map('strlen', $strings));
		while ($i < $maxLen)
		{
			$c = $strings[0][$i];
			foreach ($strings as $string)
			{
				if ($string[$i] !== $c)
				{
					break 2;
				}
			}
			$len = ++$i;
		}

		return $len;
	}

	/**
	* Get the text content of the firstChild/lastChild of each branch if they are all text nodes
	*
	* @param  string   $childType Either firstChild or lastChild
	* @return string[]            List of strings or an empty array
	*/
	protected function getTextContent($childType)
	{
		$strings = [];
		foreach ($this->getBranches() as $branch)
		{
			if (!($branch->$childType instanceof DOMText))
			{
				return [];
			}
			$strings[] = $branch->$childType->textContent;
		}

		return $strings;
	}

	/**
	* {@inheritdoc}
	*/
	protected function optimizeChoose()
	{
		if (!$this->hasOtherwise())
		{
			return;
		}

		$this->optimizeLeadingText();
		$this->optimizeTrailingText();
	}

	/**
	* Move common leading text outside of current choose
	*
	* @return void
	*/
	protected function optimizeLeadingText()
	{
		$strings = $this->getTextContent('firstChild');
		if (empty($strings))
		{
			return;
		}

		$len = $this->getPrefixLength($strings);
		if ($len)
		{
			$this->adjustTextNodes('firstChild', $len);
			$this->choose->parentNode->insertBefore(
				$this->createText(substr($strings[0], 0, $len)),
				$this->choose
			);
		}
	}

	/**
	* Move common trailing text outside of current choose
	*
	* @return void
	*/
	protected function optimizeTrailingText()
	{
		$strings = $this->getTextContent('lastChild');
		if (empty($strings))
		{
			return;
		}

		// Flip the strings before computing the prefix length to get the suffix length
		$len = $this->getPrefixLength(array_map('strrev', $strings));
		if ($len)
		{
			$this->adjustTextNodes('lastChild', 0, -$len);
			$this->choose->parentNode->insertBefore(
				$this->createText(substr($strings[0], -$len)),
				$this->choose->nextSibling
			);
		}
	}
}