<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class Comparisons extends AbstractConvertor
{
	public function getMatchers(): array
	{
		$nonzero = '(0*[1-9]\\d*)';
		$number  = '(\\d+)';
		$scalar  = '((?&Math)|(?&Number)|(?&String))';
		return array(
			'Boolean:Eq'  => $scalar  . ' (!?=) ' . $scalar,
			'Boolean:Gt'  => $scalar  . ' > '     . $number,
			'Boolean:Gte' => $scalar  . ' >= '    . $nonzero,
			'Boolean:Lt'  => $number  . ' < '     . $scalar,
			'Boolean:Lte' => $nonzero . ' <= '    . $scalar
		);
	}
	public function parseEq($expr1, $operator, $expr2)
	{
		$parsedExpr1 = $this->parser->parse($expr1);
		$parsedExpr2 = $this->parser->parse($expr2);
		$operator = $operator[0] . '=';
		if (\in_array('String', $parsedExpr1['groups'], \true) && \in_array('String', $parsedExpr2['groups'], \true))
			$operator .= '=';
		return $parsedExpr1['value'] . $operator . $parsedExpr2['value'];
	}
	public function parseGt($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '>', $expr2);
	}
	public function parseGte($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '>=', $expr2);
	}
	public function parseLt($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '<', $expr2);
	}
	public function parseLte($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '<=', $expr2);
	}
	protected function convertComparison($expr1, $operator, $expr2)
	{
		return $this->recurse($expr1) . $operator . $this->recurse($expr2);
	}
}