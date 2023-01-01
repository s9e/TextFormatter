<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class Math extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		$number = '((?&Attribute)|(?&BooleanFunction)|(?&BooleanSubExpr)|(?&MathSubExpr)|(?&Number)|(?&Parameter))';
		$math   = '((?&Math)|' . substr($number, 1);

		return [
			'Math:Addition'       => $number . ' \\+ ' . $math,
			'Math:Division'       => $number . ' div ' . $math,
			'Math:MathSubExpr'    => '\\( ((?&Math)) \\)',
			'Math:Multiplication' => $number . ' \\* ' . $math,
			'Math:Substraction'   => $number . ' - ' . $math
		];
	}

	/**
	* Convert an addition
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseAddition($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '+', $expr2);
	}

	/**
	* Convert a division
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseDivision($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '/', $expr2);
	}

	/**
	* Convert a math subexpression
	*
	* @param  string $expr
	* @return string
	*/
	public function parseMathSubExpr($expr)
	{
		return '(' . $this->recurse($expr) . ')';
	}

	/**
	* Convert a multiplication
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseMultiplication($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '*', $expr2);
	}

	/**
	* Convert a substraction
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseSubstraction($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '-', $expr2);
	}

	/**
	* Convert an operation
	*
	* @param  string $expr1
	* @param  string $operator
	* @param  string $expr2
	* @return string
	*/
	protected function convertOperation($expr1, $operator, $expr2)
	{
		$expr1 = $this->recurse($expr1);
		$expr2 = $this->recurse($expr2);

		// Prevent two consecutive minus signs to be interpreted as a post-decrement operator
		if ($operator === '-' && $expr2[0] === '-')
		{
			$operator .= ' ';
		}

		return $expr1 . $operator . $expr2;
	}
}