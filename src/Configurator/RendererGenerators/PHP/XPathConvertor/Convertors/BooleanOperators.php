<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class BooleanOperators extends AbstractConvertor
{
	public function getMatchers(): array
	{
		return array(
			'BooleanExpression:And'  => '((?&Boolean)) and ((?&BooleanExpression)|(?&Boolean))',
			'Boolean:BooleanSubExpr' => '\\( ((?&BooleanExpression)|(?&Boolean)) \\)',
			'BooleanExpression:Or'   => '((?&Boolean)) or ((?&BooleanExpression)|(?&Boolean))'
		);
	}
	public function parseAnd($expr1, $expr2)
	{
		return $this->recurse($expr1) . '&&' . $this->recurse($expr2);
	}
	public function parseBooleanSubExpr($expr)
	{
		return '(' . $this->recurse($expr) . ')';
	}
	public function parseOr($expr1, $expr2)
	{
		return $this->recurse($expr1) . '||' . $this->recurse($expr2);
	}
}