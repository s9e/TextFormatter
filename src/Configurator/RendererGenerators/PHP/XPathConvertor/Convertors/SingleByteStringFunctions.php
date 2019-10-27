<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class SingleByteStringFunctions extends AbstractConvertor
{
	public function getMatchers(): array
	{
		return array(
			'Boolean:Contains'      => 'contains \\( ((?&String)) , ((?&String)) \\)',
			'Boolean:EndsWith'      => 'ends-with \\( ((?&String)) , ((?&String)) \\)',
			'Boolean:NotContains'   => 'not \\( contains \\( ((?&String)) , ((?&String)) \\) \\)',
			'Boolean:NotEndsWith'   => 'not \\( ends-with \\( ((?&String)) , ((?&String)) \\) \\)',
			'Boolean:NotStartsWith' => 'not \\( starts-with \\( ((?&String)) , ((?&String)) \\) \\)',
			'Boolean:StartsWith'    => 'starts-with \\( ((?&String)) , ((?&String)) \\)',
			'Number:StringLength'   => 'string-length \\( ((?&String))? \\)'
		);
	}
	public function parseContains($haystack, $needle)
	{
		return $this->generateContains($haystack, $needle, \true);
	}
	public function parseEndsWith($string, $substring)
	{
		return $this->generateEndsWith($string, $substring, \true);
	}
	public function parseNotContains($haystack, $needle)
	{
		return $this->generateContains($haystack, $needle, \false);
	}
	public function parseNotEndsWith($string, $substring)
	{
		return $this->generateEndsWith($string, $substring, \false);
	}
	public function parseNotStartsWith($string, $substring)
	{
		return $this->generateStartsWith($string, $substring, \false);
	}
	public function parseStartsWith($string, $substring)
	{
		return $this->generateStartsWith($string, $substring, \true);
	}
	public function parseStringLength($expr = '.')
	{
		return "preg_match_all('(.)su'," . $this->recurse($expr) . ')';
	}
	protected function generateContains($haystack, $needle, $bool)
	{
		$operator = ($bool) ? '!==' : '===';
		return '(strpos(' . $this->recurse($haystack) . ',' . $this->recurse($needle) . ')' . $operator . 'false)';
	}
	protected function generateEndsWith($string, $substring, $bool)
	{
		return (\preg_match('(^(?:\'[^\']+\'|"[^"]+")$)D', $substring))
		     ? $this->generateEndsWithLiteral($string, $substring, $bool)
		     : $this->generateEndsWithExpression($string, $substring, $bool);
	}
	protected function generateEndsWithLiteral($string, $substring, $bool)
	{
		$operator = ($bool) ? '===' : '!==';
		return '(substr(' . $this->recurse($string) . ',-' . (\strlen($substring) - 2) . ')' . $operator . $this->recurse($substring) . ')';
	}
	protected function generateEndsWithExpression($string, $substring, $bool)
	{
		$operator = ($bool) ? '' : '!';
		return $operator . "preg_match('('.preg_quote(" . $this->recurse($substring) . ").'$)D'," . $this->recurse($string) . ')';
	}
	protected function generateStartsWith($string, $substring, $bool)
	{
		$operator = ($bool) ? '===' : '!==';
		return '(strpos(' . $this->recurse($string) . ',' . $this->recurse($substring) . ')' . $operator . '0)';
	}
}