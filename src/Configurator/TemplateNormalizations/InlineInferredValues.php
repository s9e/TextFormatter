<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMNode;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateParser;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class InlineInferredValues extends TemplateNormalization
{
	/**
	* Inline the text content of a node or the value of an attribute where it's known
	*
	* Will replace
	*     <xsl:if test="@foo='Foo'"><xsl:value-of select="@foo"/></xsl:if>
	* with
	*     <xsl:if test="@foo='Foo'">Foo</xsl:if>
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$xpath = new DOMXPath($template->ownerDocument);
		$query = '//xsl:if | //xsl:when';
		foreach ($xpath->query($query) as $node)
		{
			// Test whether the map has exactly one key and one value
			$map = TemplateParser::parseEqualityExpr($node->getAttribute('test'));
			if ($map === false || count($map) !== 1 || count($map[key($map)]) !== 1)
			{
				continue;
			}

			$expr  = key($map);
			$value = end($map[$expr]);
			$this->inlineInferredValue($node, $expr, $value);
		}
	}

	/**
	* Replace the inferred value in given node and its descendants
	*
	* @param  DOMNode $node  Context node
	* @param  string  $expr  XPath expression
	* @param  string  $value Inferred value
	* @return void
	*/
	protected function inlineInferredValue(DOMNode $node, $expr, $value)
	{
		$xpath = new DOMXPath($node->ownerDocument);

		// Get xsl:value-of descendants that match the condition
		$query = './/xsl:value-of[@select="' . $expr . '"]';
		foreach ($xpath->query($query, $node) as $valueOf)
		{
			$this->replaceValueOf($valueOf, $value);
		}

		// Get all attributes from non-XSL elements that *could* match the condition
		$query = './/*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., "{' . $expr . '}")]';
		foreach ($xpath->query($query, $node) as $attribute)
		{
			$this->replaceAttribute($attribute, $expr, $value);
		}
	}

	/**
	* Replace an expression with a literal value in given attribute
	*
	* @param  DOMAttr $attribute
	* @param  string  $expr
	* @param  string  $value
	* @return void
	*/
	protected function replaceAttribute(DOMAttr $attribute, $expr, $value)
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
	* @param  DOMElement $valueOf
	* @param  string     $value
	* @return void
	*/
	protected function replaceValueOf(DOMElement $valueOf, $value)
	{
		$valueOf->parentNode->replaceChild(
			$valueOf->ownerDocument->createTextNode($value),
			$valueOf
		);
	}
}