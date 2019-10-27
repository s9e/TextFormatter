<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use DOMNode;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
class InlineInferredValues extends AbstractNormalization
{
	protected $queries = array('//xsl:if', '//xsl:when');
	protected function normalizeElement(DOMElement $element)
	{
		$map = XPathHelper::parseEqualityExpr($element->getAttribute('test'));
		if ($map === \false || \count($map) !== 1 || \count($map[\key($map)]) !== 1)
			return;
		$expr  = \key($map);
		$value = \end($map[$expr]);
		$this->inlineInferredValue($element, $expr, $value);
	}
	protected function inlineInferredValue(DOMNode $node, $expr, $value)
	{
		$query = './/xsl:value-of[@select="' . $expr . '"]';
		foreach ($this->xpath($query, $node) as $valueOf)
			$this->replaceValueOf($valueOf, $value);
		$query = './/*[namespace-uri() != $XSL]/@*[contains(., "{' . $expr . '}")]';
		foreach ($this->xpath($query, $node) as $attribute)
			$this->replaceAttribute($attribute, $expr, $value);
	}
	protected function replaceAttribute(DOMAttr $attribute, $expr, $value)
	{
		AVTHelper::replace(
			$attribute,
			function ($token) use ($expr, $value)
			{
				if ($token[0] === 'expression' && $token[1] === $expr)
					$token = array('literal', $value);
				return $token;
			}
		);
	}
	protected function replaceValueOf(DOMElement $valueOf, $value)
	{
		$valueOf->parentNode->replaceChild($this->createText($value), $valueOf);
	}
}