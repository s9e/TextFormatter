<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class FoldConstants extends TemplateNormalization
{
	/**
	* @var array Regexps as keys, method names as values
	*/
	protected $operations = [
		'(^(\\d+) \\+ (\\d+)((?> \\+ \\d+)*)$)'  => 'foldAddition',
		'(^((?>\\d+ [-+] )*)(\\d+) div (\\d+))'  => 'foldDivision',
		'(^((?>\\d+ [-+] )*)(\\d+) \\* (\\d+))'  => 'foldMultiplication',
		'(\\( \\d+ (?>(?>[-+*]|div) \\d+ )+\\))' => 'foldSubExpression',
		'(\\( (\\d+(?>\\.\\d+)?) \\))'           => 'removeParentheses'
	];

	/**
	* Constant folding pass, limited to simple arithmetic expressions
	*
	* Will replace
	*     <iframe height="{320+30}">
	* with
	*     <iframe height="{350}">
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(.,"{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$this->replaceAVT($attribute);
		}

		foreach ($template->getElementsByTagNameNS(self::XMLNS_XSL, 'value-of') as $valueOf)
		{
			$this->replaceValueOf($valueOf);
		}
	}

	/**
	* Evaluate given expression and return the result
	*
	* @param  string $expr
	* @return string
	*/
	protected function evaluateExpression($expr)
	{
		$original = $expr;
		foreach ($this->operations as $regexp => $methodName)
		{
			$regexp = str_replace(' ', '\\s*', $regexp);
			$expr   = preg_replace_callback($regexp, [$this, $methodName], $expr);
		}

		return ($expr === $original) ? $expr : $this->evaluateExpression($expr);
	}

	/**
	* Evaluate and replace a sequence of additions
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldAddition(array $m)
	{
		return ($m[1] + $m[2]) . (empty($m[3]) ? '' : $this->evaluateExpression($m[3]));
	}

	/**
	* Evaluate and return the result of a division
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldDivision(array $m)
	{
		return $m[1] . ($m[2] / $m[3]);
	}

	/**
	* Evaluate and return the result of a multiplication
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldMultiplication(array $m)
	{
		return $m[1] . ($m[2] * $m[3]);
	}

	/**
	* Evaluate and return the result of a simple subexpression
	*
	* @param  array  $m
	* @return string
	*/
	protected function foldSubExpression(array $m)
	{
		return '(' . $this->evaluateExpression(trim(substr($m[0], 1, -1))) . ')';
	}

	/**
	* Remove the parentheses around an integer
	*
	* @param  array  $m
	* @return string
	*/
	protected function removeParentheses(array $m)
	{
		return $m[1];
	}

	/**
	* Replace constant expressions in given AVT
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function replaceAVT(DOMAttr $attribute)
	{
		AVTHelper::replace(
			$attribute,
			function ($token)
			{
				if ($token[0] === 'expression')
				{
					$token[1] = $this->evaluateExpression($token[1]);
				}

				return $token;
			}
		);
	}

	/**
	* Replace constant expressions in given xsl:value-of element
	*
	* @param  DOMElement $valueOf
	* @return void
	*/
	protected function replaceValueOf(DOMElement $valueOf)
	{
		$valueOf->setAttribute('select', $this->evaluateExpression($valueOf->getAttribute('select')));
	}
}