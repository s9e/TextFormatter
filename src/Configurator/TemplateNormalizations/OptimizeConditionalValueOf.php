<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

/**
* Remove unnecessary <xsl:if> tests around <xsl:value-of>
*
* NOTE: should be performed before attributes are inlined for maximum effect
*/
class OptimizeConditionalValueOf extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:if[count(descendant::node()) = 1]/xsl:value-of'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$if     = $element->parentNode;
		$test   = $if->getAttribute('test');
		$select = $element->getAttribute('select');

		// Ensure that the expressions match, and that they select one single attribute
		if ($select !== $test || !preg_match('#^@[-\\w]+$#D', $select))
		{
			return;
		}

		// Replace the xsl:if element with the xsl:value-of element
		$if->replaceWith($element);
	}
}