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

abstract class AbstractConstantFolding extends TemplateNormalization
{
	/**
	* Return the optimization passes supported by the concrete implementation
	*
	* @return array Regexps as keys, method names as values
	*/
	abstract protected function getOptimizationPasses();

	/**
	* Constant folding pass
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
		foreach ($this->getOptimizationPasses() as $regexp => $methodName)
		{
			$regexp = str_replace(' ', '\\s*', $regexp);
			$expr   = preg_replace_callback($regexp, [$this, $methodName], $expr);
		}

		return ($expr === $original) ? $expr : $this->evaluateExpression($expr);
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