<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineTextElements extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//xsl:text') as $node)
		{
			if (\trim($node->textContent) === '')
				if ($node->previousSibling && $node->previousSibling->nodeType === \XML_TEXT_NODE)
					;
				elseif ($node->nextSibling && $node->nextSibling->nodeType === \XML_TEXT_NODE)
					;
				else
					continue;

			$node->parentNode->replaceChild(
				$dom->createTextNode($node->textContent),
				$node
			);
		}
	}
}