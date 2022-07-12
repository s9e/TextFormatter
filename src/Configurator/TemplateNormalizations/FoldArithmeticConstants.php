<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Utils\XPath;

class FoldArithmeticConstants extends AbstractConstantFolding
{
	/**
	* {@inheritdoc}
	*/
	protected function getOptimizationPasses()
	{
		// Regular expression matching a number
		$n = '-?\\.[0-9]++|-?[0-9]++(?:\\.[0-9]++)?';

		return [
			'(^[0-9\\s]*[-+][-+0-9\\s]+$)'                               => 'foldOperation',
			'( \\+ 0(?= $| [-+\\)])|(?<![^\\(])0 \\+ )'                  => 'foldAdditiveIdentity',
			'(^((?>' . $n . ' [-+] )*)(' . $n . ') div (' . $n . '))'    => 'foldDivision',
			'(^((?>' . $n . ' [-+] )*)(' . $n . ') \\* (' . $n . '))'    => 'foldMultiplication',
			'(\\( (?:' . $n . ') (?>(?>[-+*]|div) (?:' . $n . ') )+\\))' => 'foldSubExpression',
			'((?<=[-+*\\(]|\\bdiv|^) \\( ([@$][-\\w]+|' . $n . ') \\) (?=[-+*\\)]|div|$))' => 'removeParentheses'
		];
	}

	/**
	* {@inheritdoc}
	*/
	protected function evaluateExpression($expr)
	{
		$expr = XPathHelper::encodeStrings($expr);
		$expr = parent::evaluateExpression($expr);
		$expr = XPathHelper::decodeStrings($expr);

		return $expr;
	}

	/**
	* Remove "+ 0" additions
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldAdditiveIdentity(array $m)
	{
		return '';
	}

	/**
	* Evaluate and return the result of a division
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldDivision(array $m)
	{
		return $m[1] . XPath::export($m[2] / $m[3]);
	}

	/**
	* Evaluate and return the result of a multiplication
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldMultiplication(array $m)
	{
		return $m[1] . XPath::export($m[2] * $m[3]);
	}

	/**
	* Evaluate and replace a constant operation
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldOperation(array $m)
	{
		return XPath::export($this->xpath->evaluate($m[0]));
	}

	/**
	* Evaluate and return the result of a simple subexpression
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldSubExpression(array $m)
	{
		return '(' . $this->evaluateExpression(trim(substr($m[0], 1, -1))) . ')';
	}

	/**
	* Remove the parentheses around an integer
	*
	* @param  array  $m
	* @return string
	*/
	protected function removeParentheses(array $m)
	{
		return ' ' . $m[1] . ' ';
	}
}