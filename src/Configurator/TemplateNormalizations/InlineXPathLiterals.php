<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use s9e\SweetDOM\Attr;
use s9e\SweetDOM\Element;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;

class InlineXPathLiterals extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = [
		'//xsl:value-of',
		'//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]'
	];

	/**
	* Return the textContent value of an XPath expression
	*
	* @param  string      $expr XPath expression
	* @return string|bool       Text value, or FALSE if not a literal
	*/
	protected function getTextContent($expr)
	{
		$regexp = '(^(?|\'([^\']*)\'|"([^"]*)"|0*([0-9]+(?:\\.[0-9]+)?)|(false|true)\\s*\\(\\s*\\))$)';
		if (preg_match($regexp, trim($expr), $m))
		{
			return $m[1];
		}

		return false;
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeAttribute(Attr $attribute): void
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
	protected function normalizeElement(Element $element): void
	{
		$textContent = $this->getTextContent($element->getAttribute('select'));
		if ($textContent !== false)
		{
			$element->replaceWith($this->createPolymorphicText($textContent));
		}
	}
}