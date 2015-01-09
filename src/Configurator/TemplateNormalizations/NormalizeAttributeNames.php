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

class NormalizeAttributeNames extends TemplateNormalization
{
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);

		foreach ($xpath->query('.//@*', $template) as $attribute)
		{
			$attrName = self::lowercase($attribute->localName);

			if ($attrName !== $attribute->localName)
			{
				$attribute->parentNode->setAttribute($attrName, $attribute->value);
				$attribute->parentNode->removeAttributeNode($attribute);
			}
		}

		foreach ($xpath->query('//xsl:attribute[not(contains(@name, "{"))]') as $attribute)
		{
			$attrName = self::lowercase($attribute->getAttribute('name'));

			$attribute->setAttribute('name', $attrName);
		}
	}
}