<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class PHP80Functions extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		$groups = 'Boolean:BooleanFunction:';

		return [
			$groups . 'Contains'      => 'contains \\( ((?&String)) , ((?&String)) \\)',
			$groups . 'EndsWith'      => 'ends-with \\( ((?&String)) , ((?&String)) \\)',
			$groups . 'NotContains'   => 'not \\( contains \\( ((?&String)) , ((?&String)) \\) \\)',
			$groups . 'NotEndsWith'   => 'not \\( ends-with \\( ((?&String)) , ((?&String)) \\) \\)',
			$groups . 'NotStartsWith' => 'not \\( starts-with \\( ((?&String)) , ((?&String)) \\) \\)',
			$groups . 'StartsWith'    => 'starts-with \\( ((?&String)) , ((?&String)) \\)'
		];
	}

	/**
	* Convert a call to contains()
	*
	* @param  string $haystack Expression for the haystack part of the call
	* @param  string $needle   Expression for the needle part of the call
	* @return string
	*/
	public function parseContains(string $haystack, string $needle): string
	{
		return 'str_contains(' . $this->recurse($haystack) . ',' . $this->recurse($needle) . ')';
	}

	/**
	* Convert a call to ends-with()
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseEndsWith(string $string, string $substring): string
	{
		return 'str_ends_with(' . $this->recurse($string) . ',' . $this->recurse($substring) . ')';
	}

	/**
	* Convert a call to not(contains())
	*
	* @param  string $haystack Expression for the haystack part of the call
	* @param  string $needle   Expression for the needle part of the call
	* @return string
	*/
	public function parseNotContains(string $haystack, string $needle): string
	{
		return '!str_contains(' . $this->recurse($haystack) . ',' . $this->recurse($needle) . ')';
	}

	/**
	* Convert a call to not(ends-with())
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseNotEndsWith(string $string, string $substring): string
	{
		return '!str_ends_with(' . $this->recurse($string) . ',' . $this->recurse($substring) . ')';
	}

	/**
	* Convert a call to not(starts-with())
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseNotStartsWith(string $string, string $substring): string
	{
		return '!str_starts_with(' . $this->recurse($string) . ',' . $this->recurse($substring) . ')';
	}

	/**
	* Convert a call to starts-with()
	*
	* @param  string $string    Expression for the string part of the call
	* @param  string $substring Expression for the substring part of the call
	* @return string
	*/
	public function parseStartsWith(string $string, string $substring): string
	{
		return 'str_starts_with(' . $this->recurse($string) . ',' . $this->recurse($substring) . ')';
	}
}