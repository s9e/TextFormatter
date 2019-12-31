<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class SingleByteStringFunctions extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		return [
			'Boolean:BooleanFunction:Contains'      => 'contains \\( ((?&String)) , ((?&String)) \\)',
			'Boolean:BooleanFunction:EndsWith'      => 'ends-with \\( ((?&String)) , ((?&String)) \\)',
			'Boolean:BooleanFunction:NotContains'   => 'not \\( contains \\( ((?&String)) , ((?&String)) \\) \\)',
			'Boolean:BooleanFunction:NotEndsWith'   => 'not \\( ends-with \\( ((?&String)) , ((?&String)) \\) \\)',
			'Boolean:BooleanFunction:NotStartsWith' => 'not \\( starts-with \\( ((?&String)) , ((?&String)) \\) \\)',
			'Boolean:BooleanFunction:StartsWith'    => 'starts-with \\( ((?&String)) , ((?&String)) \\)',
			'Number:StringLength'                   => 'string-length \\( ((?&String))? \\)'
		];
	}

	/**
	* Convert a call to contains()
	*
	* @param  string $haystack Expression for the haystack part of the call
	* @param  string $needle   Expression for the needle part of the call
	* @return string
	*/
	public function parseContains($haystack, $needle)
	{
		return $this->generateContains($haystack, $needle, true);
	}

	/**
	* Convert a call to ends-with()
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseEndsWith($string, $substring)
	{
		return $this->generateEndsWith($string, $substring, true);
	}

	/**
	* Convert a call to not(contains())
	*
	* @param  string $haystack Expression for the haystack part of the call
	* @param  string $needle   Expression for the needle part of the call
	* @return string
	*/
	public function parseNotContains($haystack, $needle)
	{
		return $this->generateContains($haystack, $needle, false);
	}

	/**
	* Convert a call to not(ends-with())
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseNotEndsWith($string, $substring)
	{
		return $this->generateEndsWith($string, $substring, false);
	}

	/**
	* Convert a call to not(starts-with())
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseNotStartsWith($string, $substring)
	{
		return $this->generateStartsWith($string, $substring, false);
	}

	/**
	* Convert a call to starts-with()
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseStartsWith($string, $substring)
	{
		return $this->generateStartsWith($string, $substring, true);
	}

	/**
	* Convert a call to string-length()
	*
	* @param  string $expr
	* @return string
	*/
	public function parseStringLength($expr = '.')
	{
		return "preg_match_all('(.)su'," . $this->recurse($expr) . ')';
	}

	/**
	* Generate the code for a call to contains()
	*
	* @param  string $haystack Expression for the haystack part of the call
	* @param  string $needle   Expression for the needle part of the call
	* @param  bool   $bool     Return value for a positive match
	* @return string
	*/
	protected function generateContains($haystack, $needle, $bool)
	{
		$operator = ($bool) ? '!==' : '===';

		return '(strpos(' . $this->recurse($haystack) . ',' . $this->recurse($needle) . ')' . $operator . 'false)';
	}

	/**
	* Generate the code for a call to ends-with()
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @param  bool   $bool      Return value for a positive match
	* @return string
	*/
	protected function generateEndsWith($string, $substring, $bool)
	{
		return (preg_match('(^(?:\'[^\']+\'|"[^"]+")$)D', $substring))
		     ? $this->generateEndsWithLiteral($string, $substring, $bool)
		     : $this->generateEndsWithExpression($string, $substring, $bool);
	}

	/**
	* Generate the code for a call to ends-with() where the second argument is a literal string
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for a literal substring
	* @param  bool   $bool      Return value for a positive match
	* @return string
	*/
	protected function generateEndsWithLiteral($string, $substring, $bool)
	{
		$operator = ($bool) ? '===' : '!==';

		return '(substr(' . $this->recurse($string) . ',-' . (strlen($substring) - 2) . ')' . $operator . $this->recurse($substring) . ')';
	}

	/**
	* Generate the code for a call to ends-with()
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @param  bool   $bool      Return value for a positive match
	* @return string
	*/
	protected function generateEndsWithExpression($string, $substring, $bool)
	{
		$operator = ($bool) ? '' : '!';

		return $operator . "preg_match('('.preg_quote(" . $this->recurse($substring) . ").'$)D'," . $this->recurse($string) . ')';
	}

	/**
	* Generate the code for a call to starts-with()
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @param  bool   $bool      Return value for a positive match
	* @return string
	*/
	protected function generateStartsWith($string, $substring, $bool)
	{
		$operator = ($bool) ? '===' : '!==';

		return '(strpos(' . $this->recurse($string) . ',' . $this->recurse($substring) . ')' . $operator . '0)';
	}
}