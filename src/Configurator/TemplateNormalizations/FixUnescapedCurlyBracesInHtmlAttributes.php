<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class FixUnescapedCurlyBracesInHtmlAttributes extends TemplateNormalization
{
	/**
	* Fix unescaped curly braces in HTML attributes
	*
	* Will replace
	*     <hr onclick="if(1){alert(1)}">
	*     <hr title="x{x">
	* with
	*     <hr onclick="if(1){{alert(1)}">
	*     <hr title="x{{x">
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$this->fixAttribute($attribute);
		}
	}

	/**
	* Fix unescaped braces in give attribute
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function fixAttribute(DOMAttr $attribute)
	{
		// Skip XSL elements
		if ($attribute->parentNode->namespaceURI === self::XMLNS_XSL)
		{
			return;
		}

		$match = [
			'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
			'((?<!\\{)\\{(?!\\{)[^}]*+$)',
			'((?<!\\{)\\{\\s*(?:"[^"]*"|\'[^\']*\'|[a-z]\\w*(?:\\s|:\\s|:(?:["\']|\\w+\\s*,))))i'
		];
		$replace = [
			'$0{',
			'{$0',
			'{$0'
		];
		$attrValue        = preg_replace($match, $replace, $attribute->value);
		$attribute->value = htmlspecialchars($attrValue, ENT_NOQUOTES, 'UTF-8');
	}
}