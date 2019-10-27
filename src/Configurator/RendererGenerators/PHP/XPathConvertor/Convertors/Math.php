<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class Math extends AbstractConvertor
{
	public function getMatchers(): array
	{
		$number = '((?&Attribute)|(?&MathSubExpr)|(?&Number)|(?&Parameter))';
		$math   = '((?&Math)|' . \substr($number, 1);
		return array(
			'Math:Addition'       => $number . ' \\+ ' . $math,
			'Math:Division'       => $number . ' div ' . $math,
			'Math:MathSubExpr'    => '\\( ((?&Math)) \\)',
			'Math:Multiplication' => $number . ' \\* ' . $math,
			'Math:Substraction'   => $number . ' - ' . $math
		);
	}
	public function parseAddition($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '+', $expr2);
	}
	public function parseDivision($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '/', $expr2);
	}
	public function parseMathSubExpr($expr)
	{
		return '(' . $this->recurse($expr) . ')';
	}
	public function parseMultiplication($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '*', $expr2);
	}
	public function parseSubstraction($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '-', $expr2);
	}
	protected function convertOperation($expr1, $operator, $expr2)
	{
		$expr1 = $this->recurse($expr1);
		$expr2 = $this->recurse($expr2);
		if ($operator === '-' && $expr2[0] === '-')
			$operator .= ' ';
		return $expr1 . $operator . $expr2;
	}
}