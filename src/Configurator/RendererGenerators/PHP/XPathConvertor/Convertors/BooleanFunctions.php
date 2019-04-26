<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class BooleanFunctions extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getRegexpGroups()
	{
		return [
			'BooleanParam'  => 'Boolean',
			'HasAttribute'  => 'Boolean',
			'HasAttributes' => 'Boolean',
			'Not'           => 'Boolean',
			'NotAttribute'  => 'Boolean',
			'NotParam'      => 'Boolean'
		];
	}

	/**
	* {@inheritdoc}
	*/
	public function getRegexps()
	{
		return [
			'BooleanParam'  => 'boolean \\( ((?&Parameter)) \\)',
			'HasAttribute'  => 'boolean \\( ((?&Attribute)) \\)',
			'HasAttributes' => 'boolean \\( @\\* \\)',
			'Not'           => 'not \\( ((?&Boolean)|(?&Comparison)|(?&And)|(?&Or)) \\)',
			'NotAttribute'  => 'not \\( ((?&Attribute)) \\)',
			'NotParam'      => 'not \\( ((?&Parameter)) \\)'
		];
	}

	/**
	* Convert a call to boolean() with a param
	*
	* @param  string $expr
	* @return string
	*/
	public function convertBooleanParam($expr)
	{
		return $this->convert($expr) . "!==''";
	}

	/**
	* Convert a call to boolean() with an attribute
	*
	* @param  string $expr
	* @return string
	*/
	public function convertHasAttribute($expr)
	{
		$attrName = $this->getAttributeName($expr);

		return '$node->hasAttribute(' . var_export($attrName, true) . ')';
	}

	/**
	* Convert a call to boolean(@*)
	*
	* @return string
	*/
	public function convertHasAttributes()
	{
		return '$node->attributes->length';
	}

	/**
	* Convert a call to not() with a boolean expression
	*
	* @param  string $expr
	* @return string
	*/
	public function convertNot($expr)
	{
		return '!(' . $this->convert($expr) . ')';
	}

	/**
	* Convert a call to not() with an attribute
	*
	* @param  string $expr
	* @return string
	*/
	public function convertNotAttribute($expr)
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
	public function convertNotParam($expr)
	{
		return $this->convert($expr) . "===''";
	}
}