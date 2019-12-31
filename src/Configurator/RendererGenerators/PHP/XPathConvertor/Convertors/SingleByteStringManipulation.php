<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class SingleByteStringManipulation extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		return [
			'String:Concat'          => 'concat \\( ((?&String)) ((?:, (?&String) )+)?\\)',
			'String:NormalizeSpace'  => 'normalize-space \\( ((?&String)) \\)',
			'String:SubstringAfter'  => 'substring-after \\( ((?&String)) , ((?&LiteralString)) \\)',
			'String:SubstringBefore' => 'substring-before \\( ((?&String)) , ((?&String)) \\)',
			'String:Translate'       => 'translate \\( ((?&String)) , ((?&LiteralString)) , ((?&LiteralString)) \\)'
		];
	}

	/**
	* Convert a call to concat()
	*
	* @param  string $expr1 First argument
	* @param  string $expr2 All other comma-separated arguments, starting with a comma
	* @return string
	*/
	public function parseConcat($expr1, $expr2 = null)
	{
		$php = $this->recurse($expr1);
		if (isset($expr2))
		{
			$php .= '.' . $this->recurse('concat(' . ltrim($expr2, ',') . ')');
		}

		return $php;
	}

	/**
	* Convert a call to normalize-space()
	*
	* @param  string $expr
	* @return string
	*/
	public function parseNormalizeSpace($expr)
	{
		return "preg_replace('(\\\\s+)',' ',trim(" . $this->recurse($expr) . '))';
	}

	/**
	* Convert a call to substring-after() where the second argument is a literal string
	*
	* @param  string $expr
	* @param  string $str
	* @return string
	*/
	public function parseSubstringAfter($expr, $str)
	{
		return 'substr(strstr(' . $this->recurse($expr) . ',' . $this->recurse($str) . '),' . (strlen($str) - 2) . ')';
	}

	/**
	* Convert a call to substring-before()
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function parseSubstringBefore($expr1, $expr2)
	{
		return 'strstr(' . $this->recurse($expr1) . ',' . $this->recurse($expr2) . ',true)';
	}

	/**
	* Convert a call to translate() where the second and third arguments are literal strings
	*
	* @param  string $expr
	* @param  string $from
	* @param  string $to
	* @return string
	*/
	public function parseTranslate($expr, $from, $to)
	{
		$from = $this->splitStringChars($from);
		$to   = $this->splitStringChars($to);

		// Add missing elements to $to then remove duplicates from $from and keep matching elements
		$to   = array_pad($to, count($from), '');
		$from = array_unique($from);
		$to   = array_intersect_key($to, $from);

		// Build the arguments list for the strtr() call
		$args = [$this->recurse($expr)];
		if ($this->isAsciiChars($from) && $this->isAsciiChars($to))
		{
			$args[] = $this->serializeAsciiChars($from);
			$args[] = $this->serializeAsciiChars($to);
		}
		else
		{
			$args[] = $this->serializeMap($from, $to);
		}

		return 'strtr(' . implode(',', $args) . ')';
	}

	/**
	* Test whether given list of strings contains only single ASCII characters
	*
	* @param  string[] $chars
	* @return bool
	*/
	protected function isAsciiChars(array $chars)
	{
		return ([1] === array_unique(array_map('strlen', $chars)));
	}

	/**
	* Serialize a list of ASCII chars into a single PHP string
	*
	* @param  string[] $chars
	* @return string
	*/
	protected function serializeAsciiChars(array $chars)
	{
		return var_export(implode('', $chars), true);
	}

	/**
	* Serialize the lists of characters to replace with strtr()
	*
	* @param  string[] $from
	* @param  string[] $to
	* @return string
	*/
	protected function serializeMap(array $from, array $to)
	{
		$elements = [];
		foreach ($from as $k => $str)
		{
			$elements[] = var_export($str, true) . '=>' . var_export($to[$k], true);
		}

		return '[' . implode(',', $elements) . ']';
	}

	/**
	* Split individual characters from given literal string
	*
	* @param  string   $string Original string, including quotes
	* @return string[]
	*/
	protected function splitStringChars($string)
	{
		preg_match_all('(.)su', substr($string, 1, -1), $matches);

		return $matches[0];
	}
}