<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;
class SingleByteStringManipulation extends AbstractConvertor
{
	public function getMatchers(): array
	{
		return array(
			'String:Concat'          => 'concat \\( ((?&String)) ((?:, (?&String) )+)?\\)',
			'String:NormalizeSpace'  => 'normalize-space \\( ((?&String)) \\)',
			'String:SubstringAfter'  => 'substring-after \\( ((?&String)) , ((?&LiteralString)) \\)',
			'String:SubstringBefore' => 'substring-before \\( ((?&String)) , ((?&String)) \\)',
			'String:Translate'       => 'translate \\( ((?&String)) , ((?&LiteralString)) , ((?&LiteralString)) \\)'
		);
	}
	public function parseConcat($expr1, $expr2 = \null)
	{
		$php = $this->recurse($expr1);
		if (isset($expr2))
			$php .= '.' . $this->recurse('concat(' . \ltrim($expr2, ',') . ')');
		return $php;
	}
	public function parseNormalizeSpace($expr)
	{
		return "preg_replace('(\\\\s+)',' ',trim(" . $this->recurse($expr) . '))';
	}
	public function parseSubstringAfter($expr, $str)
	{
		return 'substr(strstr(' . $this->recurse($expr) . ',' . $this->recurse($str) . '),' . (\strlen($str) - 2) . ')';
	}
	public function parseSubstringBefore($expr1, $expr2)
	{
		return 'strstr(' . $this->recurse($expr1) . ',' . $this->recurse($expr2) . ',true)';
	}
	public function parseTranslate($expr, $from, $to)
	{
		$from = $this->splitStringChars($from);
		$to   = $this->splitStringChars($to);
		$to   = \array_pad($to, \count($from), '');
		$from = \array_unique($from);
		$to   = \array_intersect_key($to, $from);
		$args = array($this->recurse($expr));
		if ($this->isAsciiChars($from) && $this->isAsciiChars($to))
		{
			$args[] = $this->serializeAsciiChars($from);
			$args[] = $this->serializeAsciiChars($to);
		}
		else
			$args[] = $this->serializeMap($from, $to);
		return 'strtr(' . \implode(',', $args) . ')';
	}
	protected function isAsciiChars(array $chars)
	{
		return (array(1) === \array_unique(\array_map('strlen', $chars)));
	}
	protected function serializeAsciiChars(array $chars)
	{
		return \var_export(\implode('', $chars), \true);
	}
	protected function serializeMap(array $from, array $to)
	{
		$elements = array();
		foreach ($from as $k => $str)
			$elements[] = \var_export($str, \true) . '=>' . \var_export($to[$k], \true);
		return '[' . \implode(',', $elements) . ']';
	}
	protected function splitStringChars($string)
	{
		\preg_match_all('(.)su', \substr($string, 1, -1), $matches);
		return $matches[0];
	}
}