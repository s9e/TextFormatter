<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class Math extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getRegexpGroups()
	{
		return [
			'Addition'       => 'Math',
			'Division'       => 'Math',
			'MathSub'        => 'Math',
			'Multiplication' => 'Math',
			'Substraction'   => 'Math'
		];
	}

	/**
	* {@inheritdoc}
	*/
	public function getRegexps()
	{
		$number = '((?&Attribute)|(?&MathSub)|(?&Number)|(?&Parameter))';
		$math   = '((?&Math)|' . substr($number, 1);

		return [
			'Addition'       => $number . ' \\+ ' . $math,
			'Division'       => $number . ' div ' . $math,
			'MathSub'        => '\\( ((?&Math)) \\)',
			'Multiplication' => $number . ' \\* ' . $math,
			'Substraction'   => $number . ' - ' . $math
		];
	}

	/**
	* Convert an addition
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function convertAddition($expr1, $expr2)
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
	public function convertDivision($expr1, $expr2)
	{
		return $this->convertOperation($expr1, '/', $expr2);
	}

	/**
	* Convert a math subexpression
	*
	* @param  string $expr
	* @return string
	*/
	public function convertMathSub($expr)
	{
		return '(' . $this->convert($expr) . ')';
	}

	/**
	* Convert a multiplication
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function convertMultiplication($expr1, $expr2)
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
	public function convertSubstraction($expr1, $expr2)
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
		$expr1 = $this->convert($expr1);
		$expr2 = $this->convert($expr2);

		// Prevent two consecutive minus signs to be interpreted as a post-decrement operator
		if ($operator === '-' && $expr2[0] === '-')
		{
			$operator .= ' ';
		}

		return $expr1 . $operator . $expr2;
	}
}