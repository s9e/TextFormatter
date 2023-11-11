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
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;

/**
* Inline the text content of a node or the value of an attribute where it's known
*
* Will replace
*     <xsl:if test="@foo='Foo'"><xsl:value-of select="@foo"/></xsl:if>
* with
*     <xsl:if test="@foo='Foo'">Foo</xsl:if>
*
* This should be applied after control structures have been optimized
*/
class InlineInferredValues extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected array $queries = ['//xsl:if', '//xsl:when'];

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(Element $element): void
	{
		// Test whether the map has exactly one key and one value
		$map = XPathHelper::parseEqualityExpr($element->getAttribute('test'));
		if ($map === false || count($map) !== 1 || count($map[key($map)]) !== 1)
		{
			return;
		}

		$expr  = key($map);
		$value = end($map[$expr]);
		$this->inlineInferredValue($element, $expr, $value);
	}

	/**
	* Replace the inferred value in given node and its descendants
	*
	* @param  Element $node  Context node
	* @param  string  $expr  XPath expression
	* @param  string  $value Inferred value
	* @return void
	*/
	protected function inlineInferredValue(Element $node, $expr, $value)
	{
		// Get xsl:value-of descendants that match the condition
		$query = './/xsl:value-of[@select="' . $expr . '"]';
		foreach ($node->query($query) as $valueOf)
		{
			$this->replaceValueOf($valueOf, $value);
		}

		// Get all attributes from non-XSL elements that *could* match the condition
		$query = './/*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{' . $expr . '}")]';
		foreach ($node->query($query) as $attribute)
		{
			$this->replaceAttribute($attribute, $expr, $value);
		}
	}

	/**
	* Replace an expression with a literal value in given attribute
	*/
	protected function replaceAttribute(Attr $attribute, string $expr, string $value)
	{
		AVTHelper::replace(
			$attribute,
			function ($token) use ($expr, $value)
			{
				// Test whether this expression is the one we're looking for
				if ($token[0] === 'expression' && $token[1] === $expr)
				{
					// Replace the expression with the value (as a literal)
					$token = ['literal', $value];
				}

				return $token;
			}
		);
	}

	/**
	* Replace an xsl:value-of element with a literal value
	*
	* @param  Element $valueOf
	* @param  string  $value
	* @return void
	*/
	protected function replaceValueOf(Element $valueOf, $value)
	{
		$valueOf->replaceWith($this->createPolymorphicText($value));
	}
}