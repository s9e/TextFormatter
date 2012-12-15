<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMXPath;
use XSLTProcessor;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Configurator\Collections\TagCollection;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;

abstract class StylesheetHelper
{
	/**
	* Return the XSL used for rendering
	*
	* @param  TagCollection    $tags
	* @param  PluginCollection $plugins
	* @return string
	*/
	public static function generate(TagCollection $tags, PluginCollection $plugins = null)
	{
		// Declare all the namespaces in use at the top
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Collect the unique prefixes used in tag names
		$prefixes = array();
		foreach ($tags as $tagName => $tag)
		{
			$pos = strpos($tagName, ':');

			if ($pos !== false)
			{
				$prefixes[substr($tagName, 0, $pos)] = 1;
			}
		}

		foreach (array_keys($prefixes) as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$xsl .= '><xsl:output method="html" encoding="utf-8" indent="no"/>';

		// Append the plugins' XSL
		if (isset($plugins))
		{
			foreach ($plugins as $plugin)
			{
				$pluginXSL = $plugin->getXSL();

				// Check that the XSL is valid
				self::checkValid($pluginXSL);

				$xsl .= $pluginXSL;
			}
		}

		// Append the tags' templates
		foreach ($tags as $tagName => $tag)
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

		// Append an empty template for <st>, <et> and <i> nodes
		$xsl .= '<xsl:template match="st|et|i"/>';

		// Append a template for <br/> nodes
		$xsl .= '<xsl:template match="br"><br/></xsl:template>';

		// Now close the stylesheet
		$xsl .= '</xsl:stylesheet>';

		// Finalize the stylesheet
		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		// Dedupes the templates
		self::dedupeTemplates($dom);

		// Serialize back to XML, trim then return
		$xsl = rtrim($dom->saveXML());

		return $xsl;
	}

	/**
	* Test whether given XSL would be legal in a stylesheet
	*
	* @throws s9e\TextFormatter\Configurator\Exceptions\InvalidXslException
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
	* @param  string      $xsl    Stylesheet
	* @param  string      $prefix New prefix
	* @return DOMDocument
	*/
	public static function changeXSLPrefix($xsl, $prefix)
	{
		$dom = new DOMDocument;
		$dom->loadXML($xsl);

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