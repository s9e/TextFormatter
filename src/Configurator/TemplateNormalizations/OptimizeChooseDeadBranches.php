<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class OptimizeChooseDeadBranches extends AbstractChooseOptimization
{
	protected function isAlwaysFalse($expr)
	{
		$regexp = '(^(?:""|\'\'|0|0*\\.0+|false\\s*\\(\\s*\\))$)';
		return (bool) \preg_match($regexp, \trim($expr));
	}
	protected function isAlwaysTrue($expr)
	{
		$regexp = '(^(?:"[^"]++"|\'[^\']++\'|0[0-9]+|[1-9][0-9]*|[0-9]*\\.0*[1-9][0-9]*|true\\s*\\(\\s*\\))$)';
		return (bool) \preg_match($regexp, \trim($expr));
	}
	protected function makeOtherwise(DOMElement $when)
	{
		$otherwise = $this->createElement('xsl:otherwise');
		while ($when->firstChild)
			$otherwise->appendChild($when->firstChild);
		$when->parentNode->replaceChild($otherwise, $when);
	}
	protected function optimizeChoose()
	{
		$removeAll = \false;
		$tests     = array();
		foreach ($this->getBranches() as $branch)
		{
			$test = \trim($branch->getAttribute('test'));
			if ($removeAll || isset($tests[$test]) || $this->isAlwaysFalse($test))
				$branch->parentNode->removeChild($branch);
			elseif ($this->isAlwaysTrue($test))
			{
				$removeAll = \true;
				$this->makeOtherwise($branch);
			}
			$tests[$test] = 1;
		}
	}
}