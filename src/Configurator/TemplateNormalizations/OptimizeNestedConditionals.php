<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Element;

/**
* Optimize xsl:choose elements by integrating the content of another xsl:choose element located
* in their xsl:otherwise part
*
* Will move child nodes from //xsl:choose/xsl:otherwise/xsl:choose to their great-grandparent as
* long as the inner xsl:choose has no siblings. Good for XSLT stylesheets because it reduces the
* number of nodes, not-so-good for the PHP renderer if it prevents from optimizing branch
* tables by mixing the branch keys
*/
class OptimizeNestedConditionals extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//xsl:choose/xsl:otherwise[count(node()) = 1]/xsl:choose',
		'//xsl:choose/xsl:otherwise[count(node()) = 1]/xsl:if'
	];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		$methodName = 'normalizeXsl' . ucfirst($element->localName);
		$this->$methodName($element);
	}

	protected function normalizeXslChoose(Element $choose): void
	{
		$otherwise   = $choose->parentNode;
		$outerChoose = $otherwise->parentNode;

		while ($choose->firstChild)
		{
			$outerChoose->appendChild($choose->firstChild);
		}

		$otherwise->remove();
	}

	protected function normalizeXslIf(Element $if): void
	{
		$otherwise = $if->parentNode;
		$when      = $otherwise->replaceWithXslWhen(test: $if->getAttribute('test'));
		while ($if->firstChild)
		{
			$when->appendChild($if->firstChild);
		}
	}
}