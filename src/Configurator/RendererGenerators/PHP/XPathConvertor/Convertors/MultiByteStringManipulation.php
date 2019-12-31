<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators\PHP\XPathConvertor\Convertors;

class MultiByteStringManipulation extends AbstractConvertor
{
	/**
	* {@inheritdoc}
	*/
	public function getMatchers(): array
	{
		return [
			'String:Substring' => 'substring \\( ((?&String)) , ((?&Attribute)|(?&Math)|(?&Number)) (?:, ((?&Attribute)|(?&Math)|(?&Number)))? \\)'
		];
	}

	/**
	* Convert a substring() function call
	*
	* @param  string $exprString
	* @param  string $exprPos
	* @param  string $exprLen
	* @return string
	*/
	public function parseSubstring($exprString, $exprPos, $exprLen = null)
	{
		// Try to fix negative lengths when possible
		if (is_numeric($exprPos) && is_numeric($exprLen) && $exprPos < 1)
		{
			$exprLen += $exprPos - 1;
		}

		$args   = [];
		$args[] = $this->recurse($exprString);
		$args[] = $this->convertPos($exprPos);
		$args[] = (isset($exprLen)) ? $this->convertLen($exprLen) : 'null';
		$args[] = "'utf-8'";

		return 'mb_substr(' . implode(',', $args) . ')';
	}

	/**
	* Convert the length expression of a substring() call
	*
	* @param  string $expr
	* @return string
	*/
	protected function convertLen($expr)
	{
		// NOTE: negative values for the second argument do not produce the same result as
		//       specified in XPath if the argument is not a literal number
		if (is_numeric($expr))
		{
			return (string) max(0, $expr);
		}

		return 'max(0,' . $this->recurse($expr) . ')';
	}

	/**
	* Convert the position expression of a substring() call
	*
	* @param  string $expr
	* @return string
	*/
	protected function convertPos($expr)
	{
		if (is_numeric($expr))
		{
			return (string) max(0, $expr - 1);
		}

		return 'max(0,' . $this->recurse($expr) . '-1)';
	}
}