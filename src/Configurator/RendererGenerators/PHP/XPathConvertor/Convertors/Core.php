<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class Core extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		return [
			'String:Attribute'     => '@ ([-\\w]++)',
			'String:Dot'           => '\\.',
			'Number:LiteralNumber' => '(-?) (\\d++)',
			'String:LiteralString' => '("[^"]*"|\'[^\']*\')',
			'String:LocalName'     => 'local-name \\(\\)',
			'String:Name'          => 'name \\(\\)',
			'String:Parameter'     => '\\$(\\w+)'
		];
	}

	/**
	* Convert the attribute syntax
	*
	* @param  string $attrName
	* @return string
	*/
	public function parseAttribute($attrName)
	{
		return '$node->getAttribute(' . var_export($attrName, true) . ')';
	}

	/**
	* Convert the dot syntax
	*
	* @return string
	*/
	public function parseDot()
	{
		return '$node->textContent';
	}

	/**
	* Convert a literal number
	*
	* @param  string $sign
	* @param  string $number
	* @return string
	*/
	public function parseLiteralNumber($sign, $number)
	{
		return $this->normalizeNumber($sign, $number);
	}

	/**
	* Convert a literal string
	*
	* @param  string $string Literal string, including the quotes
	* @return string
	*/
	public function parseLiteralString($string)
	{
		return var_export(substr($string, 1, -1), true);
	}

	/**
	* Convert a local-name() function call
	*
	* @return string
	*/
	public function parseLocalName()
	{
		return '$node->localName';
	}

	/**
	* Convert a name() function call
	*
	* @return string
	*/
	public function parseName()
	{
		return '$node->nodeName';
	}

	/**
	* Convert the parameter syntax
	*
	* @param  string $paramName
	* @return string
	*/
	public function parseParameter($paramName)
	{
		return '$this->params[' . var_export($paramName, true) . ']';
	}
}