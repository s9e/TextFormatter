<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
class FixUnescapedCurlyBracesInHtmlAttributes extends AbstractNormalization
{
	protected $queries = array('//*[namespace-uri() != $XSL]/@*[contains(., "{")]');
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$match = array(
			'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
			'(\\bfunction\\s*\\w*\\s*\\([^\\)]*\\)\\s*\\{(?!\\{))',
			'((?<!\\{)(?:\\{\\{)*\\{(?!\\{)[^}]*+$)',
			'((?<!\\{)\\{\\s*(?:"[^"]*"|\'[^\']*\'|[a-z]\\w*(?:\\s|:\\s|:(?:["\']|\\w+\\s*,))))i'
		);
		$replace = array(
			'$0{',
			'$0{',
			'{$0',
			'{$0'
		);
		$attrValue        = \preg_replace($match, $replace, $attribute->value);
		$attribute->value = \htmlspecialchars($attrValue, \ENT_NOQUOTES, 'UTF-8');
	}
}