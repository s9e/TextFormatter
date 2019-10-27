<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Utils\XPath;
class FoldArithmeticConstants extends AbstractConstantFolding
{
	protected function getOptimizationPasses()
	{
		$n = '-?\\.[0-9]++|-?[0-9]++(?:\\.[0-9]++)?';
		return array(
			'(^[0-9\\s]*[-+][-+0-9\\s]+$)'                               => 'foldOperation',
			'( \\+ 0(?= $| [-+\\)])|(?<![^\\(])0 \\+ )'                  => 'foldAdditiveIdentity',
			'(^((?>' . $n . ' [-+] )*)(' . $n . ') div (' . $n . '))'    => 'foldDivision',
			'(^((?>' . $n . ' [-+] )*)(' . $n . ') \\* (' . $n . '))'    => 'foldMultiplication',
			'(\\( (?:' . $n . ') (?>(?>[-+*]|div) (?:' . $n . ') )+\\))' => 'foldSubExpression',
			'((?<=[-+*\\(]|\\bdiv|^) \\( ([@$][-\\w]+|' . $n . ') \\) (?=[-+*\\)]|div|$))' => 'removeParentheses'
		);
	}
	public function evaluateExpression($expr)
	{
		$expr = XPathHelper::encodeStrings($expr);
		$expr = parent::evaluateExpression($expr);
		$expr = XPathHelper::decodeStrings($expr);
		return $expr;
	}
	protected function foldAdditiveIdentity(array $m)
	{
		return '';
	}
	protected function foldDivision(array $m)
	{
		return $m[1] . XPath::export($m[2] / $m[3]);
	}
	protected function foldMultiplication(array $m)
	{
		return $m[1] . XPath::export($m[2] * $m[3]);
	}
	protected function foldOperation(array $m)
	{
		return XPath::export($this->xpath->evaluate($m[0]));
	}
	protected function foldSubExpression(array $m)
	{
		return '(' . $this->evaluateExpression(\trim(\substr($m[0], 1, -1))) . ')';
	}
	protected function removeParentheses(array $m)
	{
		return ' ' . $m[1] . ' ';
	}
}