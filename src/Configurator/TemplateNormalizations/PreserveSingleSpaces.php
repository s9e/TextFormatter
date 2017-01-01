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

class PreserveSingleSpaces extends TemplateNormalization
{
	/**
	* Removes all inter-element whitespace except for single space characters
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);

		// Query all text nodes that are made of a single space and not inside of an xsl:text
		// element
		$query = '//text()[. = " "][not(parent::xsl:text)]';
		foreach ($xpath->query($query) as $textNode)
		{
			$textNode->parentNode->replaceChild(
				$dom->createElementNS(self::XMLNS_XSL, 'text', ' '),
				$textNode
			);
		}
	}
}