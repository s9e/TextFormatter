<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

/**
* De-optimize xsl:if elements so that xsl:choose dead branch elimination can apply to them
*/
class DeoptimizeIf extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//xsl:if[@test]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $if)
	{
		$choose = $this->createElement('xsl:choose');
		$when   = $choose->appendChild($this->createElement('xsl:when'));
		$when->setAttribute('test', $if->getAttribute('test'));
		while ($if->firstChild)
		{
			$when->appendChild($if->firstChild);
		}
		$if->parentNode->replaceChild($choose, $if);
	}
}