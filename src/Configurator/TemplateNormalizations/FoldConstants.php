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
	* Very limited constant folding pass
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
	}

	/**
	* Evaluate given expression and return the result
	*
	* @param  string $expr
	* @return string
	*/
	protected function evaluateExpression($expr)
	{
		if (preg_match('(^(\\d+)\\s*\\+\\s*(\\d+)$)', $expr, $m))
		{
			return $m[1] + $m[2];
		}

		return $expr;
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
}