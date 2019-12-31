<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

abstract class AbstractConstantFolding extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//*[namespace-uri() != $XSL]/@*[contains(.,"{")]',
		'//xsl:if[@test]',
		'//xsl:value-of[@select]',
		'//xsl:when[@test]'
	];

	/**
	* Return the optimization passes supported by the concrete implementation
	*
	* @return array Regexps as keys, method names as values
	*/
	abstract protected function getOptimizationPasses();

	/**
	* Evaluate given expression and return the result
	*
	* @param  string $expr
	* @return string
	*/
	protected function evaluateExpression($expr)
	{
		$original = $expr;
		foreach ($this->getOptimizationPasses() as $regexp => $methodName)
		{
			$regexp = str_replace(' ', '\\s*', $regexp);
			$expr   = preg_replace_callback($regexp, [$this, $methodName], $expr);
		}

		return ($expr === $original) ? $expr : $this->evaluateExpression(trim($expr));
	}

	/**
	* Replace constant expressions in given AVT
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		AVTHelper::replace(
			$attribute,
			function ($token)
			{
				if ($token[0] === 'expression')
				{
					$token[1] = $this->evaluateExpression($token[1]);
				}

				return $token;
			}
		);
	}

	/**
	* Replace constant expressions in given XSL element
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$attrName = ($element->localName === 'value-of') ? 'select' : 'test';
		$oldExpr  = $element->getAttribute($attrName);
		$newExpr  = $this->evaluateExpression($oldExpr);
		if ($newExpr !== $oldExpr)
		{
			$element->setAttribute($attrName, $newExpr);
		}
	}
}