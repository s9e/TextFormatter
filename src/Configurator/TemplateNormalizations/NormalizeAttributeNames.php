<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;

class NormalizeAttributeNames extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//@*', '//xsl:attribute[not(contains(@name, "{"))]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$attrName = $this->lowercase($attribute->localName);
		if ($attrName !== $attribute->localName)
		{
			$attribute->parentNode->setAttribute($attrName, $attribute->value);
			$attribute->parentNode->removeAttributeNode($attribute);
		}
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$element->setAttribute('name', $this->lowercase($element->getAttribute('name')));
	}
}