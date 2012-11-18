<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMDocument;
use DOMElement;
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
	* Optimize a template
	*
	* @param  string $template Content of the template. A root node is not required
	* @return string           Optimized template
	*/
	public static function optimize($template)
	{
		$tmp = TemplateHelper::loadTemplate($template);

		// Save single-space nodes then reload the template without whitespace
		self::preserveSingleSpaces($tmp);

		$dom = new DOMDocument;
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = false;

		// Note: for some reason, $tmp->normalizeDocument() doesn't work
		$dom->loadXML($tmp->saveXML());

		self::removeComments($dom);
		self::minifyXPathExpressions($dom);
		self::inlineElements($dom);
		self::inlineAttributes($dom);
		self::optimizeConditionalAttributes($dom);

		// Replace <xsl:text/> elements, which will restore single spaces to their original form
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
	* Preserve single space characters by replacing them with a <xsl:text/> node
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function preserveSingleSpaces(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//text()[. = " "]') as $textNode)
		{
			$newNode = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:text');
			$newNode->nodeValue = ' ';

			$textNode->parentNode->replaceChild($newNode, $textNode);
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
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//xsl:element') as $element)
		{
			$name = $element->getAttribute('name');

			if (preg_match('#^[a-z0-9]+$#', $name))
			{
				// Create the new static element
				$newElement = $dom->createElement($name);

				// Replace the old <xsl:element/> with it. We do it now so that libxml doesn't have
				// to redeclare the XSL namespace
				$element->parentNode->replaceChild($newElement, $element);

				// Now one by one and in order, we move the nodes from the old element to the new
				// one
				while ($element->firstChild)
				{
					$newElement->appendChild($element->removeChild($element->firstChild));
				}
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

		$query = '//*[namespace-uri() = ""]/xsl:attribute';

		foreach ($xpath->query($query) as $attribute)
		{
			$value = '';

			foreach ($attribute->childNodes as $childNode)
			{
				if ($childNode instanceof DOMText)
				{
					$value .= preg_replace('#([{}])#', '$1$1', $childNode->textContent);
				}
				elseif ($childNode->namespaceURI === 'http://www.w3.org/1999/XSL/Transform'
				     && $childNode->localName === 'value-of')
				{
					$value .= '{' . $childNode->getAttribute('select') . '}';
				}
				elseif ($childNode->namespaceURI === 'http://www.w3.org/1999/XSL/Transform'
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

			$attribute->parentNode->setAttribute(
				$attribute->getAttribute('name'),
				$value
			);

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
		$chars    = 'abcdefghijklmnopqrstuvwxyz0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ_';
		$DOMXPath = new DOMXPath($dom);

		$xpath = '//*[namespace-uri() = "http://www.w3.org/1999/XSL/Transform"]';
		foreach ($DOMXPath->query($xpath) as $node)
		{
			foreach ($DOMXPath->query('@match|@select|@test', $node) as $attribute)
			{
				$old = trim($attribute->nodeValue);
				$new = '';

				$pos = 0;
				$len = strlen($old);

				while ($pos < $len)
				{
					$c = $old[$pos];

					// Test for a literal string
					if ($c === '"' || $c === "'")
					{
						// Look for the matching quote
						$nextPos = strpos($old, $c, 1 + $pos);

						if ($nextPos === false)
						{
							throw new RuntimeException("Cannot parse XPath expression '" . $old . "'");
						}

						// Increment to account for the closing quote
						++$nextPos;

						$new .= substr($old, $pos, $nextPos - $pos);
						$pos = $nextPos;

						continue;
					}

					// Test whether the current expression ends with an XML name character
					if ($new === '')
					{
						$endsWithChar = false;
					}
					else
					{
						$endsWithChar = (bool) (strpos($chars, substr($new, -1)) !== false);
					}

					// Test for a character that normally appears in XML names
					$spn = strspn($old, $chars, $pos);
					if ($spn)
					{
						if ($endsWithChar)
						{
							$new .= ' ';
						}

						$new .= substr($old, $pos, $spn);
						$pos += $spn;

						continue;
					}

					if ($c === '-' && $endsWithChar)
					{
						$new .= ' ';
					}

					// Append the current char if it's not whitespace
					$new .= trim($c);

					// Move the cursor past current char
					++$pos;
				}

				$node->setAttribute($attribute->nodeName, $new);
			}
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
			$copyOf = $dom->createElementNS('http://www.w3.org/1999/XSL/Transform', 'xsl:copy-of');
			$copyOf->setAttribute('select', $if->getAttribute('test'));

			$if->parentNode->replaceChild($copyOf, $if);
		}
	}

	/**
	* Replace all <xsl:text/> nodes with a Text node
	*
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function inlineTextElements(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//xsl:text') as $xslNode)
		{
			$xslNode->parentNode->replaceChild(
				$dom->createTextNode($xslNode->textContent),
				$xslNode
			);
		}
	}
}