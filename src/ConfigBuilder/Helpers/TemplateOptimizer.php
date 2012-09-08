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

abstract class TemplateOptimizer
{
	/**
	* Optimize a template
	*
	* @param  string $template Content of the template. A root node is not required
	* @return string           Optimized template
	*/
	static public function optimize($template)
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
	* Return the XSL used for rendering
	*
	* @param  string $prefix Prefix to use for XSL elements (defaults to "xsl")
	* @return string
	*/
	static public function getXSL(ConfigBuilder $cb, $prefix = 'xsl')
	{
		// Start the stylesheet with boilerplate stuff and the /m template for rendering multiple
		// texts at once
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . '<xsl:output method="html" encoding="utf-8" indent="no"/>'
		     . '<xsl:template match="/m">'
		     . '<xsl:for-each select="*">'
		     . '<xsl:apply-templates/>'
		     . '<xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid"/></xsl:if>'
		     . '</xsl:for-each>'
		     . '</xsl:template>';

		// Append the tags' templates
		foreach ($cb->tags as $tagName => $tag)
		{
			foreach ($tag->templates as $predicate => $template)
			{
				if ($predicate !== '')
				{
					$predicate = '[' . htmlspecialchars($predicate) . ']';
				}

				$xsl .= '<xsl:template match="' . $tagName . $predicate . '">'
				      . $template
				      . '</xsl:template>';
			}
		}

		// Append the plugins' XSL
		foreach ($cb->getLoadedPlugins() as $plugin)
		{
			/**
			* @todo create a method to check XSL by loading it in XSLTProcessor - also use in loadTemplate
			*/
			$xsl .= $plugin->getXSL();
		}

		// Append the templates for <st>, <et> and <i> nodes
		$xsl .= '<xsl:template match="st|et|i"/>';

		// Now close the stylesheet
		$xsl .= '</xsl:stylesheet>';

		// Finalize the stylesheet
		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		// Dedupes the templates
		self::dedupeTemplates($dom);

		// Fix the XSL prefix
		if ($prefix !== 'xsl')
		{
			$dom = self::changeXSLPrefix($dom, $prefix);
		}

		// Add namespace declarations
		self::addNamespaceDeclarations($dom);

		return rtrim($dom->saveXML());
	}

	/**
	* Change the prefix used for XSL elements
	*
	* @param DOMDocument $dom    Stylesheet
	* @param string      $prefix New prefix
	*/
	static protected function changeXSLPrefix(DOMDocument $dom, $prefix)
	{
		$trans = new DOMDocument;
		$trans->loadXML(
			'<?xml version="1.0" encoding="utf-8"?>
			<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:' . $prefix . '="http://www.w3.org/1999/XSL/Transform">

				<xsl:output method="xml" encoding="utf-8" />

				<xsl:template match="xsl:*">
					<xsl:element name="' . $prefix . ':{local-name()}" namespace="http://www.w3.org/1999/XSL/Transform">
						<xsl:copy-of select="@*" />
						<xsl:apply-templates />
					</xsl:element>
				</xsl:template>

				<xsl:template match="node()">
					<xsl:copy>
						<xsl:copy-of select="@*" />
						<xsl:apply-templates />
					</xsl:copy>
				</xsl:template>

			</xsl:stylesheet>'
		);

		$xslt = new XSLTProcessor;
		$xslt->importStylesheet($trans);

		return $xslt->transformToDoc($dom);
	}

	/**
	* Add the namespace declarations required for the @match clauses
	*
	* @param DOMDocument $dom Stylesheet
	*/
	static protected function addNamespaceDeclarations(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//xsl:template/@match[contains(., ":")]') as $match)
		{
			$prefix = strstr($match->textContent, ':', true);

			$dom->documentElement->setAttributeNS(
				'http://www.w3.org/2000/xmlns/',
				'xmlns:' . $prefix,
				'urn:s9e:TextFormatter:' . $prefix
			);
		}
	}

	/**
	* Test whether given XSL would be legal in a stylesheet
	*
	* @param  string      $xsl Whatever would be legal under <xsl:stylesheet>
	* @return LibXMLError
	*/
	static protected function checkXSL($xsl)
	{
		$xsl = '<?xml version="1.0" encoding="utf-8" ?>'
		     . '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . $xsl
		     . '</xsl:stylesheet>';

		// Enable libxml's internal errors while we load the XSL
		$useInternalErrors = libxml_use_internal_errors(true);

		$dom = new DOMDocument;
		$error = true;

		if ($dom->loadXML($xsl))
		{
			// The XML is well-formed, now test whether it's legal XSLT
			$xslt = new XSLTProcessor;

			$error = !$xslt->importStylesheet($dom);
		}

		// Restore the previous error mechanism
		libxml_use_internal_errors($useInternalErrors);

		if ($error)
		{
			return libxml_get_last_error();
		}
	}

	/**
	* Load a template into a DOMDocument
	*
	* @param  string      $template Content of the template. A root node is not required
	* @return DOMDocument
	*/
	static protected function loadTemplate($template)
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
	static protected function saveTemplate(DOMDocument $dom)
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
	static protected function preserveSingleSpaces(DOMDocument $dom)
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
	static protected function inlineAttributes(DOMDocument $dom)
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
	static protected function normalizeSpaceInSelectAttributes(DOMDocument $dom)
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
	static protected function optimizeConditionalAttributes(DOMDocument $dom)
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
	static protected function inlineTextElements(DOMDocument $dom)
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