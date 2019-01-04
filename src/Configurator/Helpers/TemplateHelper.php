<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
	* Return all attributes (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp Regexp
	* @return array               Array of DOMNode instances
	*/
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		return NodeLocator::getAttributesByRegexp($dom, $regexp);
	}

	/**
	* Return all DOMNodes whose content is CSS
	*
	* @param  DOMDocument $dom Document
	* @return DOMNode[]        List of DOMNode instances
	*/
	public static function getCSSNodes(DOMDocument $dom)
	{
		return NodeLocator::getCSSNodes($dom);
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp Regexp
	* @return array               Array of DOMNode instances
	*/
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		return NodeLocator::getElementsByRegexp($dom, $regexp);
	}

	/**
	* Return all DOMNodes whose content is JavaScript
	*
	* @param  DOMDocument $dom Document
	* @return DOMNode[]        List of DOMNode instances
	*/
	public static function getJSNodes(DOMDocument $dom)
	{
		return NodeLocator::getJSNodes($dom);
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* Will return all <param/> descendants of <object/> and all attributes of <embed/> whose name
	* matches given regexp. This method will NOT catch <param/> elements whose 'name' attribute is
	* set via an <xsl:attribute/>
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp
	* @return array               Array of DOMNode instances
	*/
	public static function getObjectParamsByRegexp(DOMDocument $dom, $regexp)
	{
		return NodeLocator::getObjectParamsByRegexp($dom, $regexp);
	}

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
	* Return all DOMNodes whose content is an URL
	*
	* NOTE: it will also return HTML4 nodes whose content is an URI
	*
	* @param  DOMDocument $dom Document
	* @return DOMNode[]        List of DOMNode instances
	*/
	public static function getURLNodes(DOMDocument $dom)
	{
		return NodeLocator::getURLNodes($dom);
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
			$node->value .= $uniqid;
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
	* Load a template as an xsl:template node
	*
	* Will attempt to load it as XML first, then as HTML as a fallback. Either way, an xsl:template
	* node is returned
	*
	* @param  string      $template
	* @return DOMDocument
	*/
	public static function loadTemplate($template)
	{
		return TemplateLoader::load($template);
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
	* Replace parts of a template that match given regexp
	*
	* Treats attribute values as plain text. Replacements within XPath expression is unsupported.
	* The callback must return an array with two elements. The first must be either of 'expression',
	* 'literal' or 'passthrough', and the second element depends on the first.
	*
	*  - 'expression' indicates that the replacement must be treated as an XPath expression such as
	*    '@foo', which must be passed as the second element.
	*  - 'literal' indicates a literal (plain text) replacement, passed as its second element.
	*  - 'passthrough' indicates that the replacement should the tag's content. It works differently
	*    whether it is inside an attribute's value or a text node. Within an attribute's value, the
	*    replacement will be the text content of the tag. Within a text node, the replacement
	*    becomes an <xsl:apply-templates/> node.
	*
	* @param  string   $template Original template
	* @param  string   $regexp   Regexp for matching parts that need replacement
	* @param  callback $fn       Callback used to get the replacement
	* @return string             Processed template
	*/
	public static function replaceTokens($template, $regexp, $fn)
	{
		return TemplateModifier::replaceTokens($template, $regexp, $fn);
	}

	/**
	* Serialize a loaded template back into a string
	*
	* NOTE: removes the root node created by loadTemplate()
	*
	* @param  DOMDocument $dom
	* @return string
	*/
	public static function saveTemplate(DOMDocument $dom)
	{
		return TemplateLoader::save($dom);
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