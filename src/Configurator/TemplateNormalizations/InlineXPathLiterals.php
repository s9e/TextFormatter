<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

class InlineXPathLiterals extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = [
		'//xsl:value-of',
		'//*[namespace-uri() != $XSL]/@*[contains(., "{")]'
	];

	/**
	* Return the textContent value of an XPath expression
	*
	* @param  string      $expr XPath expression
	* @return string|bool       Text value, or FALSE if not a literal
	*/
	protected function getTextContent($expr)
	{
		$expr = trim($expr);

		if (preg_match('(^(?:\'[^\']*\'|"[^"]*")$)', $expr))
		{
			return substr($expr, 1, -1);
		}

		if (preg_match('(^0*([0-9]+(?:\\.[0-9]+)?)$)', $expr, $m))
		{
			// NOTE: we specifically ignore leading zeros
			return $m[1];
		}

		return false;
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		AVTHelper::replace(
			$attribute,
			function ($token)
			{
				if ($token[0] === 'expression')
				{
					$textContent = $this->getTextContent($token[1]);
					if ($textContent !== false)
					{
						// Turn this token into a literal
						$token = ['literal', $textContent];
					}
				}

				return $token;
			}
		);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$textContent = $this->getTextContent($element->getAttribute('select'));
		if ($textContent !== false)
		{
			$element->parentNode->replaceChild($this->createTextNode($textContent), $element);
		}
	}
}