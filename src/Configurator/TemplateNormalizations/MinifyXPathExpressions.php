<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class MinifyXPathExpressions extends TemplateNormalization
{
	/**
	* Remove extraneous space in XPath expressions used in XSL elements
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		// Get all the "match", "select" and "test" attributes of XSL elements, whose value contains
		// a space
		$query = '//xsl:*/@*[contains(., " ")][contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
		{
			$attribute->parentNode->setAttribute(
				$attribute->nodeName,
				XPathHelper::minify($attribute->nodeValue)
			);
		}

		// Get all the attributes of non-XSL elements, whose value contains a space
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., " ")]';
		foreach ($xpath->query($query) as $attribute)
		{
			AVTHelper::replace(
				$attribute,
				function ($token)
				{
					if ($token[0] === 'expression')
					{
						$token[1] = XPathHelper::minify($token[1]);
					}

					return $token;
				}
			);
		}
	}
}