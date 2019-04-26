<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class BooleanOperators extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getRegexpGroups()
	{
		return [
			'BooleanSub' => 'Boolean'
		];
	}

	/**
	* {@inheritdoc}
	*/
	public function getRegexps()
	{
		// Create a boolean expression that can start a recursive pattern and a more complete
		// expression that can be used in the middle of a pattern
		$booleanExpr    = '((?&And)|(?&Boolean)|(?&Comparison)|(?&Or))';
		$booleanStarter = '((?&Boolean)|(?&Comparison))';

		return [
			'And'        => $booleanStarter . ' and ' . $booleanExpr,
			'BooleanSub' => '\\( ' . $booleanExpr . ' \\)',
			'Or'         => $booleanStarter . ' or ' . $booleanExpr
		];
	}

	/**
	* Convert a "and" operation
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function convertAnd($expr1, $expr2)
	{
		return $this->convert($expr1) . '&&' . $this->convert($expr2);
	}

	/**
	* Convert a boolean subexpression
	*
	* @param  string $expr
	* @return string
	*/
	public function convertBooleanSub($expr)
	{
		return '(' . $this->convert($expr) . ')';
	}

	/**
	* Convert a "or" operation
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function convertOr($expr1, $expr2)
	{
		return $this->convert($expr1) . '||' . $this->convert($expr2);
	}
}