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

class InlineTextElements extends TemplateNormalization
{
	/**
	* Replace <xsl:text/> nodes with a Text node, except for nodes whose content is only whitespace
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//xsl:text') as $node)
		{
			// If this node's content is whitespace, ensure it's preceded or followed by a text node
			if (trim($node->textContent) === '')
			{
				if ($node->previousSibling && $node->previousSibling->nodeType === XML_TEXT_NODE)
				{
					// This node is preceded by a text node
				}
				elseif ($node->nextSibling && $node->nextSibling->nodeType === XML_TEXT_NODE)
				{
					// This node is followed by a text node
				}
				else
				{
					// This would become inter-element whitespace, therefore we can't inline
					continue;
				}
			}
			$node->parentNode->replaceChild(
				$dom->createTextNode($node->textContent),
				$node
			);
		}
	}
}