<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;

class MinifyInlineCSS extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//*[namespace-uri() != $XSL]/@style'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$css = $attribute->nodeValue;

		// Only minify if the value does not contain any XPath expression that's not an attribute
		if (!preg_match('(\\{(?!@\\w+\\}))', $css))
		{
			$attribute->nodeValue = $this->minify($css);
		}
	}

	/**
	* Minify a CSS string
	*
	* @param  string $css Original CSS
	* @return string      Minified CSS
	*/
	protected function minify($css)
	{
		$css = trim($css, " \n\t;");
		$css = preg_replace('(\\s*([,:;])\\s*)', '$1', $css);
		$css = preg_replace_callback(
			'((?<=[\\s:])#[0-9a-f]{3,6})i',
			function ($m)
			{
				return strtolower($m[0]);
			},
			$css
		);
		$css = preg_replace('((?<=[\\s:])#([0-9a-f])\\1([0-9a-f])\\2([0-9a-f])\\3)', '#$1$2$3', $css);
		$css = preg_replace('((?<=[\\s:])#f00\\b)', 'red', $css);
		$css = preg_replace('((?<=[\\s:])0px\\b)', '0', $css);

		return $css;
	}
}