<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
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
		$dom   = $template->ownerDocument;
		$xpath = new DOMXPath($dom);
		$query = '//xsl:if | //xsl:when';

		// Match an equality test between an attribute or . and a string or a number
		$regexp = '#^(@[-\\w]+|\\.)=("[^"]*"|\'[^\']*\'|\\d+)$#';

		foreach ($xpath->query($query) as $node)
		{
			if (!preg_match($regexp, $node->getAttribute('test'), $m))
			{
				continue;
			}

			$var   = $m[1];
			$value = $m[2];

			// Remove the quotes around the value
			if ($value[0] === '"' || $value[0] === "'")
			{
				$value = substr($value, 1, -1);
			}

			// Get xsl:value-of descendants that match the condition
			$query = './/xsl:value-of[@select="' . $var . '"]';
			foreach ($xpath->query($query, $node) as $valueOf)
			{
				$valueOf->parentNode->replaceChild(
					$dom->createTextNode($value),
					$valueOf
				);
			}

			// Get all attributes from non-XSL elements that *could* match the condition
			$query = './/*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
			       . '/@*[contains(., "{' . $var . '}")]';
			foreach ($xpath->query($query, $node) as $attribute)
			{
				AVTHelper::replace(
					$attribute,
					function ($token) use ($value, $var)
					{
						// Test whether this expression is the one we're looking for
						if ($token[0] === 'expression' && $token[1] === $var)
						{
							// Replace the expression with the value (as a literal)
							$token = ['literal', $value];
						}

						return $token;
					}
				);
			}
		}
	}
}