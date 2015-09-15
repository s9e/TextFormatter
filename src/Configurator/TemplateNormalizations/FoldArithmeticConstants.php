<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

class FoldArithmeticConstants extends AbstractConstantFolding
{
	/**
	* {@inheritdoc}
	*/
	protected function getOptimizationPasses()
	{
		return [
			'(^(\\d+) \\+ (\\d+)((?> \\+ \\d+)*)$)'  => 'foldAddition',
			'( \\+ 0(?! [^+\\)])|(?<![-\\w])0 \\+ )' => 'foldAdditiveIdentity',
			'(^((?>\\d+ [-+] )*)(\\d+) div (\\d+))'  => 'foldDivision',
			'(^((?>\\d+ [-+] )*)(\\d+) \\* (\\d+))'  => 'foldMultiplication',
			'(\\( \\d+ (?>(?>[-+*]|div) \\d+ )+\\))' => 'foldSubExpression',
			'(\\( (\\d+(?>\\.\\d+)?) \\))'           => 'removeParentheses'
		];
	}

	/**
	* Evaluate and replace a sequence of additions
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldAddition(array $m)
	{
		return ($m[1] + $m[2]) . (empty($m[3]) ? '' : $this->evaluateExpression($m[3]));
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
		return $m[1] . ($m[2] / $m[3]);
	}

	/**
	* Evaluate and return the result of a multiplication
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldMultiplication(array $m)
	{
		return $m[1] . ($m[2] * $m[3]);
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
		return $m[1];
	}
}