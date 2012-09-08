<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Helpers;

use DOMDocument;
use DOMXPath;
use RuntimeException;
use XSLTProcessor;
use s9e\TextFormatter\ConfigBuilder\ConfigBuilder;
use s9e\TextFormatter\ConfigBuilder\Exceptions\InvalidXslException;

/**
* Generates stylesheets based on a ConfigBuilder instance
*/
abstract class StylesheetGenerator
{
	/**
	* Return the XSL used for rendering
	*
	* @param  string $prefix Prefix to use for XSL elements (defaults to "xsl")
	* @return string
	*/
	public static function get(ConfigBuilder $cb, $xslPrefix = 'xsl')
	{
		// Declare all the namespaces in use at the top
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Collect the unique prefixes used in tag names
		$prefixes = array();
		foreach ($cb->tags as $tagName => $tag)
		{
			$pos = strpos($tagName, ':');

			if ($pos !== false)
			{
				$prefixes[substr($tagName, 0, $pos)] = 1;
			}
		}

		// Test whether a tag name uses the same prefix as the one we use for XSL elements
		if (isset($prefixes[$xslPrefix]))
		{
			throw new RuntimeException("The prefix '" . $xslPrefix . "' is already used by a tag and cannot be used for XSL elements");
		}

		foreach (array_keys($prefixes) as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		// Start the stylesheet with boilerplate stuff and the /m template for rendering multiple
		// texts at once
		$xsl .= '>'
		      . '<xsl:output method="html" encoding="utf-8" indent="no"/>'
		      . '<xsl:template match="/m">'
		      . '<xsl:for-each select="*">'
		      . '<xsl:apply-templates/>'
		      . '<xsl:if test="following-sibling::*"><xsl:value-of select="/m/@uid"/></xsl:if>'
		      . '</xsl:for-each>'
		      . '</xsl:template>';

		// Append the plugins' XSL
		foreach ($cb->getLoadedPlugins() as $plugin)
		{
			$pluginXSL = $plugin->getXSL();

			// Check that the XSL is valid
			self::checkValid($pluginXSL);

			$xsl .= $pluginXSL;
		}

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
		if ($xslPrefix !== 'xsl')
		{
			$dom = self::changeXSLPrefix($dom, $xslPrefix);
		}

		// Serialize back to XML, trim then return
		$xsl = rtrim($dom->saveXML());

		return $xsl;
	}

	/**
	* Test whether given XSL would be legal in a stylesheet
	*
	* @throws s9e\TextFormatter\ConfigBuilder\Exceptions\InvalidXslException
	*
	* @param  string $xsl Whatever would be legal under <xsl:stylesheet>
	* @return void
	*/
	public static function checkValid($xsl)
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
			throw new InvalidXslException(libxml_get_last_error()->error);
		}
	}

	/**
	* Change the prefix used for XSL elements
	*
	* @param DOMDocument $dom    Stylesheet
	* @param string      $prefix New prefix
	*/
	protected static function changeXSLPrefix(DOMDocument $dom, $prefix)
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
	* Merge identical templates together
	*
	* Works in place, by comparing templates in their serialized XML form (minus their @match
	* clause), merging both @match clauses together then removing the redundant template
	*
	* @param  DOMDocument $dom
	* @return void
	*/
	protected static function dedupeTemplates(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);
		$dupes = array();

		foreach ($xpath->query('/xsl:stylesheet/xsl:template[@match]') as $node)
		{
			// Make a copy of the template node so that we can remove its @match
			$tmp = $node->cloneNode(true);
			$tmp->removeAttribute('match');

			// Serialize the template so we can compare it as a string
			$xml = $dom->saveXML($tmp);

			if (isset($dupes[$xml]))
			{
				// It's a dupe, append its @match to the original template's @match
				$dupes[$xml]->setAttribute(
					'match',
					$dupes[$xml]->getAttribute('match') . '|' . $node->getAttribute('match')
				);

				// ...then remove the dupe from the template
				$node->parentNode->removeChild($node);
			}
			else
			{
				// Not a dupe, save the node for later
				$dupes[$xml] = $node;
			}
		}
		unset($dupes);
	}
}