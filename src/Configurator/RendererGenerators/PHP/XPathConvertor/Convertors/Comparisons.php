<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class Comparisons extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getRegexpGroups()
	{
		return [
			'Eq'  => 'Comparison',
			'Gt'  => 'Comparison',
			'Gte' => 'Comparison',
			'Lt'  => 'Comparison',
			'Lte' => 'Comparison'
		];
	}

	/**
	* {@inheritdoc}
	*/
	public function getRegexps()
	{
		$nonzero = '(0*[1-9]\\d*)';
		$number  = '(\\d+)';
		$scalar  = '((?&Math)|(?&Number)|(?&String))';

		return [
			'Eq'  => $scalar . ' (!?=) ' .  $scalar,
			'Gt'  => $scalar . ' > ' . $number,
			'Gte' => $scalar . ' >= ' . $nonzero,
			'Lt'  => $number . ' < ' . $scalar,
			'Lte' => $nonzero . ' <= ' . $scalar
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
	public function convertEq($expr1, $operator, $expr2)
	{
		$operator = $operator[0] . '=';
		if ($this->runner->getType($expr1) === 'String' && $this->runner->getType($expr2) === 'String')
		{
			$operator .= '=';
		}

		return $this->convertComparison($expr1, $operator, $expr2);
	}

	/**
	* Convert a "greater than" comparison
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function convertGt($expr1, $expr2)
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
	public function convertGte($expr1, $expr2)
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
	public function convertLt($expr1, $expr2)
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
	public function convertLte($expr1, $expr2)
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
		return $this->convert($expr1) . $operator . $this->convert($expr2);
	}
}