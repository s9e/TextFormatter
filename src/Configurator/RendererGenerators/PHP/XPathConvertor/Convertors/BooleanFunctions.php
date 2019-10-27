<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class BooleanFunctions extends AbstractConvertor
{
	public function getMatchers(): array
	{
		return array(
			'Boolean:BooleanParam'  => 'boolean \\( ((?&Parameter)) \\)',
			'Boolean:False'         => 'false \\( \\)',
			'Boolean:HasAttribute'  => 'boolean \\( ((?&Attribute)) \\)',
			'Boolean:HasAttributes' => 'boolean \\( @\\* \\)',
			'Boolean:Not'           => array(
				'order'  => 100,
				'regexp' => 'not \\( ((?&Boolean)|(?&BooleanExpression)) \\)'
			),
			'Boolean:NotAttribute'  => 'not \\( ((?&Attribute)) \\)',
			'Boolean:NotParam'      => 'not \\( ((?&Parameter)) \\)',
			'Boolean:True'          => 'true \\( \\)'
		);
	}
	public function parseBooleanParam($expr)
	{
		return $this->recurse($expr) . "!==''";
	}
	public function parseFalse()
	{
		return 'false';
	}
	public function parseHasAttribute($expr)
	{
		$attrName = $this->getAttributeName($expr);
		return '$node->hasAttribute(' . \var_export($attrName, \true) . ')';
	}
	public function parseHasAttributes()
	{
		return '$node->attributes->length';
	}
	public function parseNot($expr)
	{
		return '!(' . $this->recurse($expr) . ')';
	}
	public function parseNotAttribute($expr)
	{
		$attrName = $this->getAttributeName($expr);
		return '!$node->hasAttribute(' . \var_export($attrName, \true) . ')';
	}
	public function parseNotParam($expr)
	{
		return $this->recurse($expr) . "===''";
	}
	public function parseTrue()
	{
		return 'true';
	}
}