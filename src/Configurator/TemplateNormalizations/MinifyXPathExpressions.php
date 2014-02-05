<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
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
				TemplateHelper::minifyXPath($attribute->nodeValue)
			);
		}

		// Get all the attributes of non-XSL elements, whose value contains a space
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., " ")]';
		foreach ($xpath->query($query) as $attribute)
		{
			// Parse this attribute's value
			$tokens = TemplateHelper::parseAttributeValueTemplate($attribute->value);

			// Rebuild the attribute value
			$attrValue = '';
			foreach ($tokens as $token)
			{
				if ($token[0] === 'literal')
				{
					$attrValue .= preg_replace('([{}])', '$0$0', $token[1]);
				}
				else
				{
					$attrValue .= '{' . TemplateHelper::minifyXPath($token[1]) . '}';
				}
			}

			// Replace the attribute value
			$attribute->value = htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8');
		}
	}
}