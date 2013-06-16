<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMException;
use DOMNodeList;
use DOMText;
use DOMXPath;
use InvalidArgumentException;
use LibXMLError;
use RuntimeException;
use XSLTProcessor;

/**
* Optimizes individual templates
*/
abstract class TemplateOptimizer
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* Optimize a template
	*
	* @param  string $template Content of the template. A root node is not required
	* @return string           Optimized template
	*/
	public static function optimize($template)
	{
		$tmp = TemplateHelper::loadTemplate($template);

		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;

		// Note: for some reason, $tmp->normalizeDocument() doesn't work
		$dom->loadXML($tmp->saveXML());

		self::replaceConditionalComments($dom);
		self::removeComments($dom);
		self::minifyXPathExpressions($dom);
		self::normalizeAttributeNames($dom);
		self::normalizeElementNames($dom);
		self::optimizeConditionalValueOf($dom);
		self::inlineElements($dom);
		self::inlineAttributes($dom);
		self::optimizeConditionalAttributes($dom);

		// Replace <xsl:text/> elements, except for whitespace nodes
		self::inlineTextElements($dom);

		return TemplateHelper::saveTemplate($dom);
	}

	/**
	* Remove all comments from a document
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function removeComments(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//comment()') as $comment)
		{
			$comment->parentNode->removeChild($comment);
		}
	}

	/**
	* Replace all conditional comments from a document with an <xsl:comment/> declaration
	*
	* @link http://en.wikipedia.org/wiki/Conditional_comment
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function replaceConditionalComments(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//comment()') as $comment)
		{
			if (preg_match('#^\\[if|endif\\]$#', $comment->textContent))
			{
				$comment->parentNode->replaceChild(
					$dom->createElementNS(self::XMLNS_XSL, 'xsl:comment', $comment->textContent),
					$comment
				);
			}
		}
	}

	/**
	* Remove unnecessary <xsl:if> tests around <xsl:value-of>
	*
	* NOTE: should be performed before attributes are inlined
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function optimizeConditionalValueOf(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);
		$query = '//xsl:if[count(descendant::node()) = 1]/xsl:value-of';

		foreach ($xpath->query($query) as $valueOf)
		{
			$if     = $valueOf->parentNode;
			$test   = $if->getAttribute('test');
			$select = $valueOf->getAttribute('select');

			// Ensure that the expressions match, and that they select one single attribute
			if ($select !== $test
			 || !preg_match('#^@\\w+$#D', $select))
			{
				continue;
			}

			// Replace the <xsl:if/> node with the <xsl:value-of/> node
			$if->parentNode->replaceChild(
				$if->removeChild($valueOf),
				$if
			);
		}
	}

	/**
	* Inline the elements declarations of a template
	*
	* Will replace
	*     <xsl:element name="div"><xsl:apply-templates/></xsl:element>
	* with
	*     <div><xsl:apply-templates/></div>
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function inlineElements(DOMDocument $dom)
	{
		foreach ($dom->getElementsByTagNameNS(self::XMLNS_XSL, 'element') as $element)
		{
			$name = strtr(
				$element->getAttribute('name'),
				'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				'abcdefghijklmnopqrstuvwxyz'
			);

			try
			{
				// Create the new static element
				$newElement = ($element->hasAttribute('namespace'))
				            ? $dom->createElementNS($element->getAttribute('namespace'), $name)
				            : $dom->createElement($name);
			}
			catch (DOMException $e)
			{
				// Ignore this element and keep going if an exception got thrown
				continue;
			}

			// Replace the old <xsl:element/> with it. We do it now so that libxml doesn't have to
			// redeclare the XSL namespace
			$element->parentNode->replaceChild($newElement, $element);

			// Now one by one and in order, we move the nodes from the old element to the new one
			while ($element->firstChild)
			{
				$newElement->appendChild($element->removeChild($element->firstChild));
			}
		}
	}

	/**
	* Inline the attribute declarations of a template
	*
	* Will replace
	*     <a><xsl:attribute name="href"><xsl:value-of select="@url"/></xsl:attribute>...</a>
	* with
	*     <a href="{@url}">...</a>
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function inlineAttributes(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/xsl:attribute';

		foreach ($xpath->query($query) as $attribute)
		{
			$value = '';

			foreach ($attribute->childNodes as $childNode)
			{
				if ($childNode instanceof DOMText)
				{
					$value .= preg_replace('#([{}])#', '$1$1', $childNode->textContent);
				}
				elseif ($childNode->namespaceURI === self::XMLNS_XSL
				     && $childNode->localName === 'value-of')
				{
					$value .= '{' . $childNode->getAttribute('select') . '}';
				}
				elseif ($childNode->namespaceURI === self::XMLNS_XSL
				     && $childNode->localName === 'text')
				{
					$value .= preg_replace('#([{}])#', '$1$1', $childNode->textContent);
				}
				else
				{
					// Can't inline this attribute, move on to the next one
					continue 2;
				}
			}

			try
			{
				$attribute->parentNode->setAttribute($attribute->getAttribute('name'), $value);
			}
			catch (DOMException $e)
			{
				// Ignore this attribute and keep going if an exception got thrown
				continue;
			}

			$attribute->parentNode->removeChild($attribute);
		}
	}

	/**
	* Remove extraneous space in XPath expressions used in XSL elements
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function minifyXPathExpressions(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($dom->getElementsByTagNameNS(self::XMLNS_XSL, '*') as $node)
		{
			foreach ($xpath->query('@match|@select|@test', $node) as $attribute)
			{
				$node->setAttribute(
					$attribute->nodeName,
					self::minifyXPath($attribute->nodeValue)
				);
			}
		}

		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*';
		foreach ($xpath->query($query) as $attribute)
		{
			// Parse this attribute's value
			$tokens = TemplateHelper::parseAttributeValueTemplate($attribute->value);

			// Rebuild the attribute value
			$attrValue = '';
			foreach ($tokens as $token)
			{
				if ($token[0] === 'literal')
				{
					$attrValue .= preg_replace('/([{}])/', '$1$1', $token[1]);
				}
				else
				{
					$attrValue .= '{' . self::minifyXPath($token[1]) . '}';
				}
			}

			// Replace the attribute value
			$attribute->value = htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8');
		}
	}

	/**
	* Remove extraneous space in a given XPath expression
	*
	* @param  string $expr Original XPath expression
	* @return string       Minified XPath expression
	*/
	public static function minifyXPath($expr)
	{
		$old     = $expr;
		$strings = [];

		// Trim the surrounding whitespace then temporarily remove literal strings
		$expr = preg_replace_callback(
			'/(?:"[^"]*"|\'[^\']*\')/',
			function ($m) use (&$strings)
			{
				$uniqid = '_' . sha1(uniqid()) . '_';
				$strings[$uniqid] = $m[0];

				return $uniqid;
			},
			trim($expr)
		);

		if (preg_match('/[\'"]/', $expr))
		{
			throw new RuntimeException("Cannot parse XPath expression '" . $old . "'");
		}

		// Normalize whitespace to a single space
		$expr = preg_replace('/\\s+/', ' ', $expr);

		// Remove the space between a non-word character and a word character
		$expr = preg_replace('/([-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);
		$expr = preg_replace('/([^-a-z_0-9]) ([-a-z_0-9])/i', '$1$2', $expr);

		// Remove the space between two non-word characters as long as they're not two -
		$expr = preg_replace('/(?!- -)([^-a-z_0-9]) ([^-a-z_0-9])/i', '$1$2', $expr);

		// Remove the space between a - and a word character, as long as there's a space before -
		$expr = preg_replace('/ - ([a-z_0-9])/i', ' -$1', $expr);

		// Restore the literals
		$expr = strtr($expr, $strings);

		return $expr;
	}

	/**
	* Lowercase attribute names
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function normalizeAttributeNames(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		// Normalize elements' attributes
		foreach ($xpath->query('//*') as $element)
		{
			$attributes = [];
			foreach ($xpath->query('@*', $element) as $attribute)
			{
				$attrName = strtr(
					$attribute->localName,
					'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
					'abcdefghijklmnopqrstuvwxyz'
				);

				// Record the value of this attribute (if it's the first of its name) then remove
				// the attribute
				if (!isset($attributes[$attrName]))
				{
					$attributes[$attrName] = $attribute->value;
				}

				$element->removeAttributeNode($attribute);
			}

			foreach ($attributes as $attrName => $attrValue)
			{
				$element->setAttribute($attrName, $attrValue);
			}
		}

		// Normalize <xsl:attribute/> names
		foreach ($xpath->query('//xsl:attribute[not(contains(@name, "{"))]') as $attribute)
		{
			$attrName = strtr(
				$attribute->getAttribute('name'),
				'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				'abcdefghijklmnopqrstuvwxyz'
			);

			$attribute->setAttribute('name', $attrName);
		}
	}

	/**
	* Lowercase element names
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function normalizeElementNames(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//*[namespace-uri() != "' . self::XMLNS_XSL . '"]') as $element)
		{
			$elName = strtr(
				$element->localName,
				'ABCDEFGHIJKLMNOPQRSTUVWXYZ',
				'abcdefghijklmnopqrstuvwxyz'
			);

			if ($elName === $element->localName)
			{
				continue;
			}

			// Create a new element with the correct name
			$newElement = $dom->createElementNS($element->namespaceURI, $elName);

			// Move every child to the new element
			while ($element->firstChild)
			{
				$newElement->appendChild($element->removeChild($element->firstChild));
			}

			// Copy attributes to the new node
			foreach ($element->attributes as $attribute)
			{
				$newElement->setAttributeNS(
					$attribute->namespaceURI,
					$attribute->nodeName,
					$attribute->value
				);
			}

			// Replace the old element with the new one
			$element->parentNode->replaceChild($newElement, $element);
		}
	}

	/**
	* Optimize conditional attributes
	*
	* Will replace conditional attributes with a <xsl:copy-of/>, e.g.
	*	<xsl:if test="@foo">
	*		<xsl:attribute name="foo">
	*			<xsl:value-of select="@foo" />
	*		</xsl:attribute>
	*	</xsl:if>
	* into
	*	<xsl:copy-of select="@foo"/>
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function optimizeConditionalAttributes(DOMDocument $dom)
	{
		$query = '//xsl:if'
		       . "[starts-with(@test, '@')]"
		       . '[count(descendant::node()) = 2]'
		       . '[xsl:attribute[@name = substring(../@test, 2)][xsl:value-of[@select = ../../@test]]]';

		$xpath = new DOMXPath($dom);

		foreach ($xpath->query($query) as $if)
		{
			$copyOf = $dom->createElementNS(self::XMLNS_XSL, 'xsl:copy-of');
			$copyOf->setAttribute('select', $if->getAttribute('test'));

			$if->parentNode->replaceChild($copyOf, $if);
		}
	}

	/**
	* Replace <xsl:text/> nodes with a Text node, except for nodes whose content is only whitespace
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function inlineTextElements(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//xsl:text') as $node)
		{
			// If this node's content is whitespace, ensure it's preceded or followed by a text node
			if (trim($node->textContent) === '')
			{
				if ($node->previousSibling && $node->previousSibling->nodeType === XML_TEXT_NODE)
				{
					// This node is preceded by a text node
				}
				elseif ($node->nextSibling && $node->nextSibling->nodeType === XML_TEXT_NODE)
				{
					// This node is followed by a text node
				}
				else
				{
					// This would become inter-element whitespace, therefore we can't inline
					continue;
				}
			}

			$node->parentNode->replaceChild(
				$dom->createTextNode($node->textContent),
				$node
			);
		}
	}
}