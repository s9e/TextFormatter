<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class Core extends AbstractConvertor
{
	public function getMatchers(): array
	{
		return array(
			'String:Attribute'     => '@ ([-\\w]+)',
			'String:Dot'           => '\\.',
			'Number:LiteralNumber' => '(-?) (\\d++)',
			'String:LiteralString' => '("[^"]*"|\'[^\']*\')',
			'String:LocalName'     => 'local-name \\(\\)',
			'String:Name'          => 'name \\(\\)',
			'String:Parameter'     => '\\$(\\w+)'
		);
	}
	public function parseAttribute($attrName)
	{
		return '$node->getAttribute(' . \var_export($attrName, \true) . ')';
	}
	public function parseDot()
	{
		return '$node->textContent';
	}
	public function parseLiteralNumber($sign, $number)
	{
		return $this->normalizeNumber($sign, $number);
	}
	public function parseLiteralString($string)
	{
		return \var_export(\substr($string, 1, -1), \true);
	}
	public function parseLocalName()
	{
		return '$node->localName';
	}
	public function parseName()
	{
		return '$node->nodeName';
	}
	public function parseParameter($paramName)
	{
		return '$this->params[' . \var_export($paramName, \true) . ']';
	}
}