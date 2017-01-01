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

class InlineCDATA extends TemplateNormalization
{
	/**
	* Replace CDATA sections with text literals
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//text()') as $textNode)
		{
			if ($textNode->nodeType === XML_CDATA_SECTION_NODE)
			{
				$textNode->parentNode->replaceChild(
					$dom->createTextNode($textNode->textContent),
					$textNode
				);
			}
		}
	}
}