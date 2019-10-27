<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class OptimizeConditionalValueOf extends AbstractNormalization
{
	protected $queries = array('//xsl:if[count(descendant::node()) = 1]/xsl:value-of');
	protected function normalizeElement(DOMElement $element)
	{
		$if     = $element->parentNode;
		$test   = $if->getAttribute('test');
		$select = $element->getAttribute('select');
		if ($select !== $test || !\preg_match('#^@[-\\w]+$#D', $select))
			return;
		$if->parentNode->replaceChild($if->removeChild($element), $if);
	}
}