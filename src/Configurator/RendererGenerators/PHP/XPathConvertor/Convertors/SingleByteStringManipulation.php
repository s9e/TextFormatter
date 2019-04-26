<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class SingleByteStringManipulation extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getRegexpGroups()
	{
		return [
			'Concat'          => 'String',
			'NormalizeSpace'  => 'String',
			'SubstringAfter'  => 'String',
			'SubstringBefore' => 'String',
			'Translate'       => 'String'
		];
	}

	/**
	* {@inheritdoc}
	*/
	public function getRegexps()
	{
		return [
			'Concat'          => 'concat \\( ((?&String)) ((?:, (?&String) )+)?\\)',
			'NormalizeSpace'  => 'normalize-space \\( ((?&String)) \\)',
			'SubstringAfter'  => 'substring-after \\( ((?&String)) , ((?&LiteralString)) \\)',
			'SubstringBefore' => 'substring-before \\( ((?&String)) , ((?&String)) \\)',
			'Translate'       => 'translate \\( ((?&String)) , ((?&LiteralString)) , ((?&LiteralString)) \\)'
		];
	}

	/**
	* Convert a call to concat()
	*
	* @param  string $expr1 First argument
	* @param  string $expr2 All other comma-separated arguments, starting with a comma
	* @return string
	*/
	public function convertConcat($expr1, $expr2 = null)
	{
		$php = $this->convert($expr1);
		if (isset($expr2))
		{
			$php .= '.' . $this->convert('concat(' . ltrim($expr2, ',') . ')');
		}

		return $php;
	}

	/**
	* Convert a call to normalize-space()
	*
	* @param  string $expr
	* @return string
	*/
	public function convertNormalizeSpace($expr)
	{
		return "preg_replace('(\\\\s+)',' ',trim(" . $this->convert($expr) . '))';
	}

	/**
	* Convert a call to substring-after() where the second argument is a literal string
	*
	* @param  string $expr
	* @param  string $str
	* @return string
	*/
	public function convertSubstringAfter($expr, $str)
	{
		return 'substr(strstr(' . $this->convert($expr) . ',' . $this->convert($str) . '),' . (strlen($str) - 2) . ')';
	}

	/**
	* Convert a call to substring-before()
	*
	* @param  string $expr1
	* @param  string $expr2
	* @return string
	*/
	public function convertSubstringBefore($expr1, $expr2)
	{
		return 'strstr(' . $this->convert($expr1) . ',' . $this->convert($expr2) . ',true)';
	}

	/**
	* Convert a call to translate() where the second and third arguments are literal strings
	*
	* @param  string $expr
	* @param  string $from
	* @param  string $to
	* @return string
	*/
	public function convertTranslate($expr, $from, $to)
	{
		$from = $this->splitStringChars($from);
		$to   = $this->splitStringChars($to);

		// Add missing elements to $to then remove duplicates from $from and keep matching elements
		$to   = array_pad($to, count($from), '');
		$from = array_unique($from);
		$to   = array_intersect_key($to, $from);

		// Build the arguments list for the strtr() call
		$args = [$this->convert($expr)];
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