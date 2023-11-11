<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Attr;

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
	protected array $queries = ['//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(Attr $attribute): void
	{
		$match = [
			'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
			'(\\bfunction\\s*\\w*\\s*\\([^\\)]*\\)\\s*\\{(?!\\{))',
			'(=(?:>|&gt;)\\s*\\{(?!\\{))',
			'((?<!\\{)(?:\\{\\{)*\\{(?!\\{)[^}]*+$)',
			'((?<!\\{)\\{\\s*(?:"[^"]*"|\'[^\']*\'|[a-z]\\w*(?:\\s|:\\s|:(?:["\']|\\w+\\s*,))))i'
		];
		$replace = [
			'$0{',
			'$0{',
			'$0{',
			'{$0',
			'{$0'
		];
		$attrValue        = preg_replace($match, $replace, $attribute->value);
		$attribute->value = htmlspecialchars($attrValue, ENT_NOQUOTES, 'UTF-8');
	}
}