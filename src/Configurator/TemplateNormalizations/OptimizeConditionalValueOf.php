<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class OptimizeConditionalValueOf extends TemplateNormalization
{
	/**
	* Remove unnecessary <xsl:if> tests around <xsl:value-of>
	*
	* NOTE: should be performed before attributes are inlined for maximum effect
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if[count(descendant::node()) = 1]/xsl:value-of';
		foreach ($xpath->query($query) as $valueOf)
		{
			$if     = $valueOf->parentNode;
			$test   = $if->getAttribute('test');
			$select = $valueOf->getAttribute('select');

			// Ensure that the expressions match, and that they select one single attribute
			if ($select !== $test
			 || !preg_match('#^@[-\\w]+$#D', $select))
			{
				continue;
			}

			// Replace the <xsl:if/> node with the <xsl:value-of/> node
			$if->parentNode->replaceChild(
				$if->removeChild($valueOf),
				$if
			);
		}
	}
}