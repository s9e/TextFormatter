<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMText;
use DOMXPath;

abstract class TemplateHelper
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* Return a list of parameters in use in given XSL
	*
	* @param  string $xsl XSL source
	* @return array       Alphabetically sorted list of unique parameter names
	*/
	public static function getParametersFromXSL($xsl)
	{
		$paramNames = [];
		$xpath      = new DOMXPath(TemplateLoader::load($xsl));

		// Start by collecting XPath expressions in XSL elements
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
		{
			$expr        = $attribute->value;
			$paramNames += array_flip(self::getParametersFromExpression($attribute, $expr));
		}

		// Collect XPath expressions in attribute value templates
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (AVTHelper::parse($attribute->value) as $token)
			{
				if ($token[0] === 'expression')
				{
					$expr        = $token[1];
					$paramNames += array_flip(self::getParametersFromExpression($attribute, $expr));
				}
			}
		}

		// Sort the parameter names and return them in a list
		ksort($paramNames);

		return array_keys($paramNames);
	}

	/**
	* Highlight the source of a node inside of a template
	*
	* @param  DOMNode $node    Node to highlight
	* @param  string  $prepend HTML to prepend
	* @param  string  $append  HTML to append
	* @return string           Template's source, as HTML
	*/
	public static function highlightNode(DOMNode $node, $prepend, $append)
	{
		// Create a copy of the document that we can modify without side effects
		$dom = $node->ownerDocument->cloneNode(true);
		$dom->formatOutput = true;

		$xpath = new DOMXPath($dom);
		$node  = $xpath->query($node->getNodePath())->item(0);

		// Add a unique token to the node
		$uniqid = uniqid('_');
		if ($node instanceof DOMAttr)
		{
			$node->value = htmlspecialchars($node->value, ENT_NOQUOTES, 'UTF-8') . $uniqid;
		}
		elseif ($node instanceof DOMElement)
		{
			$node->setAttribute($uniqid, '');
		}
		elseif ($node instanceof DOMCharacterData || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
		}

		$docXml = TemplateLoader::innerXML($dom->documentElement);
		$docXml = trim(str_replace("\n  ", "\n", $docXml));

		$nodeHtml = htmlspecialchars(trim($dom->saveXML($node)));
		$docHtml  = htmlspecialchars($docXml);

		// Enclose the node's representation in our highlighting HTML
		$html = str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);

		// Remove the unique token from HTML
		$html = str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		$html = str_replace($uniqid, '', $html);

		return $html;
	}

	/**
	* Replace simple templates (in an array, in-place) with a common template
	*
	* In some situations, renderers can take advantage of multiple tags having the same template. In
	* any configuration, there's almost always a number of "simple" tags that are rendered as an
	* HTML element of the same name with no HTML attributes. For instance, the system tag "p" used
	* for paragraphs, "B" tags used for "b" HTML elements, etc... This method replaces those
	* templates with a common template that uses a dynamic element name based on the tag's name,
	* either its nodeName or localName depending on whether the tag is namespaced, and normalized to
	* lowercase using XPath's translate() function
	*
	* @param  array<string> &$templates Associative array of [tagName => template]
	* @param  integer       $minCount
	* @return void
	*/
	public static function replaceHomogeneousTemplates(array &$templates, $minCount = 3)
	{
		// Prepare the XPath expression used for the element's name
		$expr = 'name()';

		// Identify "simple" tags, whose template is one element of the same name. Their template
		// can be replaced with a dynamic template shared by all the simple tags
		$tagNames = [];
		foreach ($templates as $tagName => $template)
		{
			// Generate the element name based on the tag's localName, lowercased
			$elName = strtolower(preg_replace('/^[^:]+:/', '', $tagName));
			if ($template === '<' . $elName . '><xsl:apply-templates/></' . $elName . '>')
			{
				$tagNames[] = $tagName;

				// Use local-name() if any of the tags are namespaced
				if (strpos($tagName, ':') !== false)
				{
					$expr = 'local-name()';
				}
			}
		}

		// We only bother replacing their template if there are at least $minCount simple tags.
		// Otherwise it only makes the stylesheet bigger
		if (count($tagNames) < $minCount)
		{
			return;
		}

		// Generate a list of uppercase characters from the tags' names
		$chars = preg_replace('/[^A-Z]+/', '', count_chars(implode('', $tagNames), 3));
		if ($chars > '')
		{
			$expr = 'translate(' . $expr . ",'" . $chars . "','" . strtolower($chars) . "')";
		}

		// Prepare the common template
		$template = '<xsl:element name="{' . $expr . '}"><xsl:apply-templates/></xsl:element>';

		// Replace the templates
		foreach ($tagNames as $tagName)
		{
			$templates[$tagName] = $template;
		}
	}

	/**
	* Get a list of parameters from given XPath expression
	*
	* @param  DOMNode  $node Context node
	* @param  string   $expr XPath expression
	* @return string[]
	*/
	protected static function getParametersFromExpression(DOMNode $node, $expr)
	{
		$varNames   = XPathHelper::getVariables($expr);
		$paramNames = [];
		$xpath      = new DOMXPath($node->ownerDocument);
		foreach ($varNames as $name)
		{
			// Test whether this is the name of a local variable
			$query = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $name . '"]';
			if (!$xpath->query($query, $node)->length)
			{
				$paramNames[] = $name;
			}
		}

		return $paramNames;
	}
}