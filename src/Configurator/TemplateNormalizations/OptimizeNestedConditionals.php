<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class OptimizeNestedConditionals extends TemplateNormalization
{
	/**
	* Optimize xsl:choose elements by integrating the content of another xsl:choose element located
	* in their xsl:otherwise part
	*
	* Will move child nodes from //xsl:choose/xsl:otherwise/xsl:choose to their great-grandparent as
	* long as the inner xsl:choose has no siblings. Good for XSLT stylesheets because it reduces the
	* number of nodes, not-so-good for the PHP renderer when it prevents from optimizing branch
	* tables by mixing the branch keys
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:choose/xsl:otherwise[count(node()) = 1]/xsl:choose';
		foreach ($xpath->query($query) as $innerChoose)
		{
			$otherwise   = $innerChoose->parentNode;
			$outerChoose = $otherwise->parentNode;

			while ($innerChoose->firstChild)
			{
				$outerChoose->appendChild($innerChoose->removeChild($innerChoose->firstChild));
			}

			$outerChoose->removeChild($otherwise);
		}
	}
}