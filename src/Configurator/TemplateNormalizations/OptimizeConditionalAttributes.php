<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class OptimizeConditionalAttributes extends AbstractNormalization
{
	protected $queries = array('//xsl:if[starts-with(@test, "@")][count(descendant::node()) = 2][xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]');
	protected function normalizeElement(DOMElement $element)
	{
		$copyOf = $this->createElement('xsl:copy-of');
		$copyOf->setAttribute('select', $element->getAttribute('test'));
		$element->parentNode->replaceChild($copyOf, $element);
	}
}