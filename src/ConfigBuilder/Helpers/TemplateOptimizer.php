<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Helpers;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNodeList;
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
		$dom = self::loadTemplate($template);

		// Save single-space nodes then reload the template without whitespace
		self::preserveSingleSpaces($dom);
		$dom->preserveWhiteSpace = false;
		$dom->normalizeDocument();

		/**
		* @todo replace select=" @ foo " with select="@foo"
		*/

		self::inlineAttributes($dom);
		self::optimizeConditionalAttributes($dom);

		// Replace <xsl:text/> elements, which will restore single spaces to their original form
		self::inlineTextElements($dom);

		return self::saveTemplate($dom);
	}

	/**
	* Load a template into a DOMDocument
	*
	* @param  string      $template Content of the template. A root node is not required
	* @return DOMDocument
	*/
	protected static function loadTemplate($template)
	{
		// Put the template inside of a <xsl:template/> node
		$xsl = '<?xml version="1.0" encoding="utf-8" ?>'
		     . '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $template
		     . '</xsl:template>';

		// Enable libxml's internal errors while we load the template
		$useInternalErrors = libxml_use_internal_errors(true);

		$dom = new DOMDocument;
		$error = !$dom->loadXML($xsl);

		// Restore the previous error mechanism
		libxml_use_internal_errors($useInternalErrors);

		return ($error) ? libxml_get_last_error() : $dom;
	}

	/**
	* Serialize a loaded template back into a string
	*
	* @param  DOMDocument $dom
	* @return string
	*/
	protected static function saveTemplate(DOMDocument $dom)
	{
		// Serialize the XML then remove the outer node
		$xml = $dom->saveXML($dom->documentElement);

		$pos = 1 + strpos($xml, '>');
		$len = strrpos($xml, '<') - $pos;

		$xml = substr($xml, $pos, $len);

		return $xml;
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
			$newNode->textContent = ' ';

			$textNode->parentNode->replaceChild($newNode, $textNode);
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

		$query = '//*[namespace-uri() = ""]'
		       . '/xsl:attribute[count(descendant::node()) = 1]'
		       . '/xsl:value-of[@select]';

		foreach ($xpath->query($query) as $valueOf)
		{
			$attribute = $valueOf->parentNode;

			$attribute->parentNode->setAttribute(
				$attribute->getAttribute('name'),
				'{' . $valueOf->getAttribute('select') . '}'
			);

			$attribute->parentNode->removeChild($attribute);
		}
	}

	/**
	* @param DOMDocument $dom xsl:template node
	*/
	protected static function normalizeSpaceInSelectAttributes(DOMDocument $dom)
	{
		$DOMXPath = new DOMXPath($dom);

		$xpath = '//*[namespace-uri() = "http://www.w3.org/1999/XSL/Transform"]/@select';
		foreach ($DOMXPath->query($xpath) as $node)
		{
			$node->setAttribute(
				'select',
				preg_replace('#^@\\s+#', '@', trim($node->getAttribute('select')))
			);
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