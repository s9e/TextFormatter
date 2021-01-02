<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class BooleanOperators extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		return [
			'BooleanExpression:And'  => '((?&Boolean)) and ((?&BooleanExpression)|(?&Boolean))',
			'Boolean:BooleanSubExpr' => '\\( ((?&BooleanExpression)|(?&Boolean)) \\)',
			'BooleanExpression:Or'   => '((?&Boolean)) or ((?&BooleanExpression)|(?&Boolean))'
		];
	}

	/**
	* Convert a "and" operation
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseAnd($expr1, $expr2)
	{
		return $this->recurse($expr1) . '&&' . $this->recurse($expr2);
	}

	/**
	* Convert a boolean subexpression
	*
	* @param  string $expr
	* @return string
	*/
	public function parseBooleanSubExpr($expr)
	{
		return '(' . $this->recurse($expr) . ')';
	}

	/**
	* Convert a "or" operation
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseOr($expr1, $expr2)
	{
		return $this->recurse($expr1) . '||' . $this->recurse($expr2);
	}
}