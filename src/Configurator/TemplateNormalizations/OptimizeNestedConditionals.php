<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

/**
* Optimize xsl:choose elements by integrating the content of another xsl:choose element located
* in their xsl:otherwise part
*
* Will move child nodes from //xsl:choose/xsl:otherwise/xsl:choose to their great-grandparent as
* long as the inner xsl:choose has no siblings. Good for XSLT stylesheets because it reduces the
* number of nodes, not-so-good for the PHP renderer when it prevents from optimizing branch
* tables by mixing the branch keys
*/
class OptimizeNestedConditionals extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//xsl:choose/xsl:otherwise[count(node()) = 1]/xsl:choose'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$otherwise   = $element->parentNode;
		$outerChoose = $otherwise->parentNode;

		while ($element->firstChild)
		{
			$outerChoose->appendChild($element->removeChild($element->firstChild));
		}

		$outerChoose->removeChild($otherwise);
	}
}