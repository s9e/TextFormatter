<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
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
	* with
	*     <hr onclick="if(1){{alert(1)}">
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
		$parentNode = $attribute->parentNode;

		// Skip XSL elements
		if ($parentNode->namespaceURI === self::XMLNS_XSL)
		{
			return;
		}

		$attribute->value = htmlspecialchars(
			preg_replace(
				'(\\b(?:do|else|(?:if|while)\\s*\\(.*?\\))\\s*\\{(?![{@]))',
				'$0{',
				$attribute->value
			),
			ENT_NOQUOTES,
			'UTF-8'
		);
	}
}