<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class DeoptimizeIf extends AbstractNormalization
{
	protected $queries = array('//xsl:if[@test]');
	protected function normalizeElement(DOMElement $if)
	{
		$choose = $this->createElement('xsl:choose');
		$when   = $choose->appendChild($this->createElement('xsl:when'));
		$when->setAttribute('test', $if->getAttribute('test'));
		while ($if->firstChild)
			$when->appendChild($if->firstChild);
		$if->parentNode->replaceChild($choose, $if);
	}
}