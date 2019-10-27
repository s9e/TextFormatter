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
abstract class AbstractConstantFolding extends AbstractNormalization
{
	protected $queries = array(
		'//*[namespace-uri() != $XSL]/@*[contains(.,"{")]',
		'//xsl:if[@test]',
		'//xsl:value-of[@select]',
		'//xsl:when[@test]'
	);
	abstract protected function getOptimizationPasses();
	public function evaluateExpression($expr)
	{
		$original = $expr;
		foreach ($this->getOptimizationPasses() as $regexp => $methodName)
		{
			$regexp = \str_replace(' ', '\\s*', $regexp);
			$expr   = \preg_replace_callback($regexp, array($this, $methodName), $expr);
		}
		return ($expr === $original) ? $expr : $this->evaluateExpression(\trim($expr));
	}
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$_this = $this;
		AVTHelper::replace(
			$attribute,
			function ($token) use ($_this)
			{
				if ($token[0] === 'expression')
					$token[1] = $_this->evaluateExpression($token[1]);
				return $token;
			}
		);
	}
	protected function normalizeElement(DOMElement $element)
	{
		$attrName = ($element->localName === 'value-of') ? 'select' : 'test';
		$oldExpr  = $element->getAttribute($attrName);
		$newExpr  = $this->evaluateExpression($oldExpr);
		if ($newExpr !== $oldExpr)
			$element->setAttribute($attrName, $newExpr);
	}
}