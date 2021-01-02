<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;

class MinifyXPathExpressions extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//@*[contains(., " ") or contains(., ")")]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$element = $attribute->parentNode;
		if (!$this->isXsl($element))
		{
			// Replace XPath expressions in non-XSL elements
			$this->replaceAVT($attribute);
		}
		elseif (in_array($attribute->nodeName, ['match', 'select', 'test'], true))
		{
			// Replace the content of match, select and test attributes of an XSL element
			$expr = XPathHelper::minify($attribute->nodeValue);
			$element->setAttribute($attribute->nodeName, $expr);
		}
	}

	/**
	* Minify XPath expressions in given attribute
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function replaceAVT(DOMAttr $attribute)
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