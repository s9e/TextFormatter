<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
class MinifyXPathExpressions extends AbstractNormalization
{
	protected $queries = array('//@*[contains(., " ")]');
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$element = $attribute->parentNode;
		if (!$this->isXsl($element))
			$this->replaceAVT($attribute);
		elseif (\in_array($attribute->nodeName, array('match', 'select', 'test'), \true))
		{
			$expr = XPathHelper::minify($attribute->nodeValue);
			$element->setAttribute($attribute->nodeName, $expr);
		}
	}
	protected function replaceAVT(DOMAttr $attribute)
	{
		AVTHelper::replace(
			$attribute,
			function ($token)
			{
				if ($token[0] === 'expression')
					$token[1] = XPathHelper::minify($token[1]);
				return $token;
			}
		);
	}
}