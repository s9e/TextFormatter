<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class BooleanFunctions extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		$groups = 'Boolean:BooleanFunction:';

		return [
			$groups . 'BooleanParam'  => 'boolean \\( ((?&Parameter)) \\)',
			$groups . 'False'         => 'false \\( \\)',
			$groups . 'HasAttribute'  => 'boolean \\( ((?&Attribute)) \\)',
			$groups . 'HasAttributes' => 'boolean \\( @\\* \\)',
			$groups . 'Not'           => [
				// Only try matching generic not() invocations after special cases fail
				'order'  => 100,
				'regexp' => 'not \\( ((?&Boolean)|(?&BooleanExpression)) \\)'
			],
			$groups . 'NotAttribute'  => 'not \\( ((?&Attribute)) \\)',
			$groups . 'NotParam'      => 'not \\( ((?&Parameter)) \\)',
			$groups . 'True'          => 'true \\( \\)'
		];
	}

	/**
	* Convert a call to boolean() with a param
	*
	* @param  string $expr
	* @return string
	*/
	public function parseBooleanParam($expr)
	{
		return $this->recurse($expr) . "!==''";
	}

	/**
	* Convert a call to false()
	*
	* @return string
	*/
	public function parseFalse()
	{
		return 'false';
	}

	/**
	* Convert a call to boolean() with an attribute
	*
	* @param  string $expr
	* @return string
	*/
	public function parseHasAttribute($expr)
	{
		$attrName = $this->getAttributeName($expr);

		return '$node->hasAttribute(' . var_export($attrName, true) . ')';
	}

	/**
	* Convert a call to boolean(@*)
	*
	* @return string
	*/
	public function parseHasAttributes()
	{
		return '$node->attributes->length';
	}

	/**
	* Convert a call to not() with a boolean expression
	*
	* @param  string $expr
	* @return string
	*/
	public function parseNot($expr)
	{
		return '!(' . $this->recurse($expr) . ')';
	}

	/**
	* Convert a call to not() with an attribute
	*
	* @param  string $expr
	* @return string
	*/
	public function parseNotAttribute($expr)
	{
		$attrName = $this->getAttributeName($expr);

		return '!$node->hasAttribute(' . var_export($attrName, true) . ')';
	}

	/**
	* Convert a call to not() with a param
	*
	* @param  string $expr
	* @return string
	*/
	public function parseNotParam($expr)
	{
		return $this->recurse($expr) . "===''";
	}

	/**
	* Convert a call to true()
	*
	* @return string
	*/
	public function parseTrue()
	{
		return 'true';
	}
}