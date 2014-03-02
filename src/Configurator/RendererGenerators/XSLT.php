<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\RendererGenerators;

use s9e\TextFormatter\Configurator\RendererGenerator;
use s9e\TextFormatter\Configurator\Rendering;
use s9e\TextFormatter\Renderers\XSLT as XSLTRenderer;

class XSLT implements RendererGenerator
{
	/**
	* {@inheritdoc}
	*/
	public function getRenderer(Rendering $rendering)
	{
		return new XSLTRenderer($this->getXSL($rendering));
	}

	/**
	* Generate an XSL stylesheet based on given rendering configuration
	*
	* @param  Rendering $rendering
	* @return string
	*/
	public function getXSL(Rendering $rendering)
	{
		$groupedTemplates = [];
		$prefixes         = [];
		$simpleTags       = [];
		$templates        = $rendering->getTemplates();

		// Identify "simple" tags, whose template is one element of the same name. Their template
		// can be replaced with a dynamic template shared by all the simple tags
		foreach ($templates as $tagName => $template)
		{
			// Generate the element name based on the tag's localName, lowercased
			$elName = strtolower(preg_replace('/^[^:]+:/', '', $tagName));

			// Generate the corresponding simple template
			$simpleTemplate = '<' . $elName . '><xsl:apply-templates/></' . $elName . '>';

			if ($template === $simpleTemplate)
			{
				$simpleTags[] = $tagName;
			}
		}

		// We only bother replacing their template if there are at least 3 simple tags. Otherwise
		// it only makes the stylesheet bigger
		if (count($simpleTags) > 2)
		{
			// Prepare the XPath expression used for the element's name
			$expr = 'name()';

			// Use local-name() if any of the simple tags are namespaced
			foreach ($simpleTags as $tagName)
			{
				if (strpos($tagName, ':') !== false)
				{
					$expr = 'local-name()';
					break;
				}
			}

			// Generate a list of uppercase characters from the tags' names
			$chars = preg_replace('/[^A-Z]+/', '', count_chars(implode('', $simpleTags), 3));

			if ($chars)
			{
				$expr = 'translate(' . $expr . ",'" . $chars . "','" . strtolower($chars) . "')";
			}

			$template = '<xsl:element name="{' . $expr . '}">'
			          . '<xsl:apply-templates/>'
			          . '</xsl:element>';

			foreach ($simpleTags as $tagName)
			{
				$templates[$tagName] = $template;
			}
		}

		// Group tags with identical templates together
		foreach ($templates as $tagName => $template)
		{
			$groupedTemplates[$template][] = $tagName;

			// Record the tag's prefix if applicable
			$pos = strpos($tagName, ':');
			if ($pos !== false)
			{
				$prefixes[substr($tagName, 0, $pos)] = 1;
			}
		}

		// Declare all the namespaces in use at the top
		$xsl = '<?xml version="1.0" encoding="utf-8"?>'
		     . '<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';

		// Append the namespace declarations to the stylesheet
		$prefixes = array_keys($prefixes);
		sort($prefixes);
		foreach ($prefixes as $prefix)
		{
			$xsl .= ' xmlns:' . $prefix . '="urn:s9e:TextFormatter:' . $prefix . '"';
		}

		/**
		* Exclude those prefixes to keep the HTML neat
		*
		* @link http://lenzconsulting.com/namespaces-in-xslt/#exclude-result-prefixes
		*/
		if ($prefixes)
		{
			$xsl .= ' exclude-result-prefixes="' . implode(' ', $prefixes) . '"';
		}

		// Start the stylesheet with the boilerplate stuff
		$outputMethod = ($rendering->type === 'html') ? 'html' : 'xml';
		$xsl .= '><xsl:output method="' . $outputMethod . '" encoding="utf-8" indent="no"';
		if ($outputMethod === 'xml')
		{
			$xsl .= ' omit-xml-declaration="yes"';
		}
		$xsl .= '/>';

		// Add stylesheet parameters
		foreach ($rendering->getAllParameters() as $paramName => $paramValue)
		{
			$xsl .= '<xsl:param name="' . htmlspecialchars($paramName) . '"';

			if ($paramValue === '')
			{
				$xsl .= '/>';
			}
			else
			{
				$xsl .= '>' . htmlspecialchars($paramValue) . '</xsl:param>';
			}
		}

		// Add templates
		foreach ($groupedTemplates as $template => $tagNames)
		{
			// Open the template element
			$xsl .= '<xsl:template match="' . implode('|', $tagNames) . '"';

			// Make it a self-closing element if the template is empty
			if ($template === '')
			{
				$xsl .= '/>';
			}
			else
			{
				$xsl .= '>' . $template . '</xsl:template>';
			}
		}

		$xsl .= '</xsl:stylesheet>';

		return $xsl;
	}
}