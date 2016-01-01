<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class NormalizeAttributeNames extends TemplateNormalization
{
	/**
	* Lowercase attribute names
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		// Normalize elements' attributes
		foreach ($xpath->query('.//@*', $template) as $attribute)
		{
			$attrName = self::lowercase($attribute->localName);

			if ($attrName !== $attribute->localName)
			{
				$attribute->parentNode->setAttribute($attrName, $attribute->value);
				$attribute->parentNode->removeAttributeNode($attribute);
			}
		}

		// Normalize <xsl:attribute/> names
		foreach ($xpath->query('//xsl:attribute[not(contains(@name, "{"))]') as $attribute)
		{
			$attrName = self::lowercase($attribute->getAttribute('name'));

			$attribute->setAttribute('name', $attrName);
		}
	}
}