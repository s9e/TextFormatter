<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;

/**
* Fix unescaped curly braces in HTML attributes
*
* Will replace
*     <hr onclick="if(1){alert(1)}">
*     <hr title="x{x">
* with
*     <hr onclick="if(1){{alert(1)}">
*     <hr title="x{{x">
*/
class FixUnescapedCurlyBracesInHtmlAttributes extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//*[namespace-uri() != $XSL]/@*[contains(., "{")]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$match = [
			'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
			'(\\bfunction\\s*\\w*\\s*\\([^\\)]*\\)\\s*\\{(?!\\{))',
			'((?<!\\{)(?:\\{\\{)*\\{(?!\\{)[^}]*+$)',
			'((?<!\\{)\\{\\s*(?:"[^"]*"|\'[^\']*\'|[a-z]\\w*(?:\\s|:\\s|:(?:["\']|\\w+\\s*,))))i'
		];
		$replace = [
			'$0{',
			'$0{',
			'{$0',
			'{$0'
		];
		$attrValue        = preg_replace($match, $replace, $attribute->value);
		$attribute->value = htmlspecialchars($attrValue, ENT_NOQUOTES, 'UTF-8');
	}
}