<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class Comparisons extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		$nonzero = '(0*[1-9]\\d*)';
		$number  = '(\\d+)';
		$scalar  = '((?&Math)|(?&Number)|(?&String))';

		return [
			'Boolean:Eq'  => $scalar  . ' (!?=) ' . $scalar,
			'Boolean:Gt'  => $scalar  . ' > '     . $number,
			'Boolean:Gte' => $scalar  . ' >= '    . $nonzero,
			'Boolean:Lt'  => $number  . ' < '     . $scalar,
			'Boolean:Lte' => $nonzero . ' <= '    . $scalar
		];
	}

	/**
	* Convert an equality test
	*
	* @param  string $expr1
	* @param  string $operator
	* @param  string $expr2
	* @return string
	*/
	public function parseEq($expr1, $operator, $expr2)
	{
		$parsedExpr1 = $this->parser->parse($expr1);
		$parsedExpr2 = $this->parser->parse($expr2);

		$operator = $operator[0] . '=';
		if (in_array('String', $parsedExpr1['groups'], true) && in_array('String', $parsedExpr2['groups'], true))
		{
			$operator .= '=';
		}

		return $parsedExpr1['value'] . $operator . $parsedExpr2['value'];
	}

	/**
	* Convert a "greater than" comparison
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseGt($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '>', $expr2);
	}

	/**
	* Convert a "greater than or equal to" comparison
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseGte($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '>=', $expr2);
	}

	/**
	* Convert a "less than" comparison
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseLt($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '<', $expr2);
	}

	/**
	* Convert a "less than or equal to" comparison
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseLte($expr1, $expr2)
	{
		return $this->convertComparison($expr1, '<=', $expr2);
	}

	/**
	* Convert a comparison
	*
	* @param  string $expr1
	* @param  string $operator
	* @param  string $expr2
	* @return string
	*/
	protected function convertComparison($expr1, $operator, $expr2)
	{
		return $this->recurse($expr1) . $operator . $this->recurse($expr2);
	}
}