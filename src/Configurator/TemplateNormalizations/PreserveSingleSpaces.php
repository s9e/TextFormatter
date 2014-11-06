<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class PreserveSingleSpaces extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);

		$query = '//text()[. = " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
			$textNode->parentNode->replaceChild(
				$dom->createElementNS(self::XMLNS_XSL, 'text', ' '),
				$textNode
			);
	}
}