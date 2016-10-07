<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMDocument;
use DOMXPath;

class FoldArithmeticConstants extends AbstractConstantFolding
{
	/**
	* @var DOMXPath
	*/
	protected $xpath;

	/**
	* Constructor
	*/
	public function __construct()
	{
		$this->xpath = new DOMXPath(new DOMDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function getOptimizationPasses()
	{
		return [
			'(^[-+0-9\\s]+$)'                        => 'foldOperation',
			'( \\+ 0(?! [^+\\)])|(?<![-\\w])0 \\+ )' => 'foldAdditiveIdentity',
			'(^((?>\\d+ [-+] )*)(\\d+) div (\\d+))'  => 'foldDivision',
			'(^((?>\\d+ [-+] )*)(\\d+) \\* (\\d+))'  => 'foldMultiplication',
			'(\\( \\d+ (?>(?>[-+*]|div) \\d+ )+\\))' => 'foldSubExpression',
			'((?<=[-+*\\(]|\\bdiv|^) \\( ([@$][-\\w]+|\\d+(?>\\.\\d+)?) \\) (?=[-+*\\)]|div|$))' => 'removeParentheses'
		];
	}

	/**
	* {@inheritdoc}
	*/
	protected function evaluateExpression($expr)
	{
		$expr = preg_replace_callback(
			'(([\'"])(.*?)\\1)s',
			function ($m)
			{
				return $m[1] . bin2hex($m[2]) . $m[1];
			},
			$expr
		);
		$expr = parent::evaluateExpression($expr);
		$expr = preg_replace_callback(
			'(([\'"])(.*?)\\1)s',
			function ($m)
			{
				return $m[1] . hex2bin($m[2]) . $m[1];
			},
			$expr
		);

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
	* Evaluate and replace a constant operation
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldOperation(array $m)
	{
		return (string) $this->xpath->evaluate($m[0]);
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