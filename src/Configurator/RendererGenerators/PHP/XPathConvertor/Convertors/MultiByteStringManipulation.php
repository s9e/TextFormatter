<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class MultiByteStringManipulation extends AbstractConvertor
{
	public function getMatchers(): array
	{
		return array(
			'String:Substring' => 'substring \\( ((?&String)) , ((?&Attribute)|(?&Math)|(?&Number)) (?:, ((?&Attribute)|(?&Math)|(?&Number)))? \\)'
		);
	}
	public function parseSubstring($exprString, $exprPos, $exprLen = \null)
	{
		if (\is_numeric($exprPos) && \is_numeric($exprLen) && $exprPos < 1)
			$exprLen += $exprPos - 1;
		$args   = array();
		$args[] = $this->recurse($exprString);
		$args[] = $this->convertPos($exprPos);
		$args[] = (isset($exprLen)) ? $this->convertLen($exprLen) : 'null';
		$args[] = "'utf-8'";
		return 'mb_substr(' . \implode(',', $args) . ')';
	}
	protected function convertLen($expr)
	{
		if (\is_numeric($expr))
			return (string) \max(0, $expr);
		return 'max(0,' . $this->recurse($expr) . ')';
	}
	protected function convertPos($expr)
	{
		if (\is_numeric($expr))
			return (string) \max(0, $expr - 1);
		return 'max(0,' . $this->recurse($expr) . '-1)';
	}
}