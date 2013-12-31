<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class NormalizeAttributeNames extends TemplateNormalization
{
	/**
	* Lowercase attribute names
	*
	* @param  DOMNode $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMNode $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		// Normalize elements' attributes
		foreach ($xpath->query('.//@*', $template) as $attribute)
		{
			$attrName = strtr(
				$attribute->localName,
				'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				'abcdefghijklmnopqrstuvwxyz'
			);

			if ($attrName !== $attribute->localName)
			{
				$attribute->parentNode->setAttribute($attrName, $attribute->value);
				$attribute->parentNode->removeAttributeNode($attribute);
			}
		}

		// Normalize <xsl:attribute/> names
		foreach ($xpath->query('//xsl:attribute[not(contains(@name, "{"))]') as $attribute)
		{
			$attrName = strtr(
				$attribute->getAttribute('name'),
				'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				'abcdefghijklmnopqrstuvwxyz'
			);

			$attribute->setAttribute('name', $attrName);
		}
	}
}