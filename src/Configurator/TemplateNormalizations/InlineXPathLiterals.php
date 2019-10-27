<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
class InlineXPathLiterals extends AbstractNormalization
{
	protected $queries = array(
		'//xsl:value-of',
		'//*[namespace-uri() != $XSL]/@*[contains(., "{")]'
	);
	public function getTextContent($expr)
	{
		$regexp = '(^(?|\'([^\']*)\'|"([^"]*)"|0*([0-9]+(?:\\.[0-9]+)?)|(false|true)\\s*\\(\\s*\\))$)';
		if (\preg_match($regexp, \trim($expr), $m))
			return $m[1];
		return \false;
	}
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$_this = $this;
		AVTHelper::replace(
			$attribute,
			function ($token) use ($_this)
			{
				if ($token[0] === 'expression')
				{
					$textContent = $_this->getTextContent($token[1]);
					if ($textContent !== \false)
						$token = array('literal', $textContent);
				}
				return $token;
			}
		);
	}
	protected function normalizeElement(DOMElement $element)
	{
		$textContent = $this->getTextContent($element->getAttribute('select'));
		if ($textContent !== \false)
			$element->parentNode->replaceChild($this->createText($textContent), $element);
	}
}