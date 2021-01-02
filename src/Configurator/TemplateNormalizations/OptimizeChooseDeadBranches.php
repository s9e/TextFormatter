<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

class OptimizeChooseDeadBranches extends AbstractChooseOptimization
{
	/**
	* Test whether given XPath expression is always false
	*
	* @param  string $expr
	* @return bool
	*/
	protected function isAlwaysFalse($expr)
	{
		// Always false: empty strings, 0, or false()
		$regexp = '(^(?:""|\'\'|(?:0*\\.)?0+|false\\s*\\(\\s*\\))$)';

		return (bool) preg_match($regexp, trim($expr));
	}

	/**
	* Test whether given XPath expression is always true
	*
	* @param  string $expr
	* @return bool
	*/
	protected function isAlwaysTrue($expr)
	{
		// Always true: non-empty strings, non-0 numbers, or true()
		$regexp = '(^(?:"[^"]++"|\'[^\']++\'|0*[1-9][0-9]*(?:\\.[0-9]*)?|0*\\.0*[1-9][0-9]*|true\\s*\\(\\s*\\))$)';

		return (bool) preg_match($regexp, trim($expr));
	}

	/**
	* Convert given xsl:when element into an xsl:otherwise element
	*
	* @param  DOMElement $when
	* @return void
	*/
	protected function makeOtherwise(DOMElement $when)
	{
		$otherwise = $this->createElement('xsl:otherwise');
		while ($when->firstChild)
		{
			$otherwise->appendChild($when->firstChild);
		}

		$when->parentNode->replaceChild($otherwise, $when);
	}

	/**
	* {@inheritdoc}
	*/
	protected function optimizeChoose()
	{
		$removeAll = false;
		$tests     = [];
		foreach ($this->getBranches() as $branch)
		{
			$test = trim($branch->getAttribute('test'));

			if ($removeAll || isset($tests[$test]) || $this->isAlwaysFalse($test))
			{
				$branch->parentNode->removeChild($branch);
			}
			elseif ($this->isAlwaysTrue($test))
			{
				$removeAll = true;
				$this->makeOtherwise($branch);
			}

			$tests[$test] = 1;
		}
	}
}