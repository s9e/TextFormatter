<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

abstract class TemplateHelper
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

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
		$dom = new DOMDocument;

		// First try as XML
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadXML($xml);
		libxml_use_internal_errors($useErrors);

		if ($success)
		{
			return $dom;
		}

		// Try fixing unescaped ampersands and replacing HTML entities
		$tmp = preg_replace('(&(?![A-Za-z0-9]+;|#\\d+;|#x[A-Fa-f0-9]+;))', '&amp;', $template);
		$tmp = preg_replace_callback(
			'(&(?!quot;|amp;|apos;|lt;|gt;)\\w+;)',
			function ($m)
			{
				return html_entity_decode($m[0], ENT_NOQUOTES, 'UTF-8');
			},
			$tmp
		);
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $tmp . '</xsl:template>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadXML($xml);
		libxml_use_internal_errors($useErrors);

		if ($success)
		{
			return $dom;
		}

		// If the template contains an XSL element, abort now. Otherwise, try reparsing it as HTML
		if (strpos($template, '<xsl:') !== false)
		{
			$error = libxml_get_last_error();
			throw new InvalidXslException($error->message);
		}

		// Fall back to loading it inside a div, as HTML
		$html = '<?xml version="1.0" encoding="utf-8" ?><html><body><div>' . $template . '</div></body></html>';

		$useErrors = libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_use_internal_errors($useErrors);

		// Now dump the thing as XML and reload it with the proper namespace declaration
		$xml = self::innerXML($dom->documentElement->firstChild->firstChild);

		return self::loadTemplate($xml);
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
		return self::innerXML($dom->documentElement);
	}

	/**
	* Get the XML content of an element
	*
	* @param  DOMElement $element
	* @return string
	*/
	protected static function innerXML(DOMElement $element)
	{
		// Serialize the XML then remove the outer element
		$xml = $element->ownerDocument->saveXML($element);

		$pos = 1 + strpos($xml, '>');
		$len = strrpos($xml, '<') - $pos;

		// If the template is empty, return an empty string
		if ($len < 1)
		{
			return '';
		}

		$xml = substr($xml, $pos, $len);

		return $xml;
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

		// Wrap the XSL in boilerplate code because it might not have a root element
		$xsl = '<xsl:stylesheet xmlns:xsl="' . self::XMLNS_XSL . '">'
		     . '<xsl:template>'
		     . $xsl
		     . '</xsl:template>'
		     . '</xsl:stylesheet>';

		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$xpath = new DOMXPath($dom);

		// Start by collecting XPath expressions in XSL elements
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (XPathHelper::getVariables($attribute->value) as $varName)
			{
				// Test whether this is the name of a local variable
				$varQuery = 'ancestor-or-self::*/'
				          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

				if (!$xpath->query($varQuery, $attribute)->length)
				{
					$paramNames[] = $varName;
				}
			}
		}

		// Collecting XPath expressions in attribute value templates
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$tokens = AVTHelper::parse($attribute->value);

			foreach ($tokens as $token)
			{
				if ($token[0] !== 'expression')
				{
					continue;
				}

				foreach (XPathHelper::getVariables($token[1]) as $varName)
				{
					// Test whether this is the name of a local variable
					$varQuery = 'ancestor-or-self::*/'
					          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

					if (!$xpath->query($varQuery, $attribute)->length)
					{
						$paramNames[] = $varName;
					}
				}
			}
		}

		// Dedupe and sort names
		$paramNames = array_unique($paramNames);
		sort($paramNames);

		return $paramNames;
	}

	/**
	* Return all attributes (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp Regexp
	* @return array               Array of DOMNode instances
	*/
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//@*') as $attribute)
		{
			if (preg_match($regexp, $attribute->name))
			{
				$nodes[] = $attribute;
			}
		}

		// Get generated attributes
		foreach ($xpath->query('//xsl:attribute') as $attribute)
		{
			if (preg_match($regexp, $attribute->getAttribute('name')))
			{
				$nodes[] = $attribute;
			}
		}

		// Get attributes created with <xsl:copy-of/>
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (preg_match('/^@(\\w+)$/', $expr, $m)
			 && preg_match($regexp, $m[1]))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
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
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//*') as $element)
		{
			if (preg_match($regexp, $element->localName))
			{
				$nodes[] = $element;
			}
		}

		// Get generated elements
		foreach ($xpath->query('//xsl:element') as $element)
		{
			if (preg_match($regexp, $element->getAttribute('name')))
			{
				$nodes[] = $element;
			}
		}

		// Get elements created with <xsl:copy-of/>
		// NOTE: this method of creating elements is disallowed by default
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (preg_match('/^\\w+$/', $expr)
			 && preg_match($regexp, $expr))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
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
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Collect attributes from <embed/> elements
		foreach (self::getAttributesByRegexp($dom, $regexp) as $attribute)
		{
			if ($attribute->nodeType === XML_ATTRIBUTE_NODE)
			{
				if (strtolower($attribute->parentNode->localName) === 'embed')
				{
					$nodes[] = $attribute;
				}
			}
			elseif ($xpath->evaluate('ancestor::embed', $attribute))
			{
				// Assuming <xsl:attribute/> or <xsl:copy-of/>
				$nodes[] = $attribute;
			}
		}

		// Collect <param/> descendants of <object/> elements
		foreach ($dom->getElementsByTagName('object') as $object)
		{
			foreach ($object->getElementsByTagName('param') as $param)
			{
				if (preg_match($regexp, $param->getAttribute('name')))
				{
					$nodes[] = $param;
				}
			}
		}

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is CSS
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^style$/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is JavaScript
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?>data-s9e-livepreview-postprocess$|on)/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is an URL
	*
	* NOTE: it will also return HTML4 nodes whose content is an URI
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?>^(?>action|background|c(?>ite|lassid|odebase)|data|formaction|href|icon|longdesc|manifest|p(?>luginspage|oster|rofile)|usemap)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);

		/**
		* @link http://helpx.adobe.com/flash/kb/object-tag-syntax-flash-professional.html
		* @link http://www.sitepoint.com/control-internet-explorer/
		*/
		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
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
		if ($template === '')
		{
			return $template;
		}

		$dom   = self::loadTemplate($template);
		$xpath = new DOMXPath($dom);

		// Replace tokens in attributes
		foreach ($xpath->query('//@*') as $attribute)
		{
			// Generate the new value
			$attrValue = preg_replace_callback(
				$regexp,
				function ($m) use ($fn, $attribute)
				{
					$replacement = $fn($m, $attribute);

					if ($replacement[0] === 'expression')
					{
						return '{' . $replacement[1] . '}';
					}
					elseif ($replacement[0] === 'passthrough')
					{
						return '{.}';
					}
					else
					{
						// Literal replacement
						return $replacement[1];
					}
				},
				$attribute->value
			);

			// Replace the attribute value
			$attribute->value = htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8');
		}

		// Replace tokens in text nodes
		foreach ($xpath->query('//text()') as $node)
		{
			preg_match_all(
				$regexp,
				$node->textContent,
				$matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			if (empty($matches))
			{
				continue;
			}

			// Grab the node's parent so that we can rebuild the text with added variables right
			// before the node, using DOM's insertBefore(). Technically, it would make more sense
			// to create a document fragment, append nodes then replace the node with the fragment
			// but it leads to namespace redeclarations, which looks ugly
			$parentNode = $node->parentNode;

			$lastPos = 0;
			foreach ($matches as $m)
			{
				$pos = $m[0][1];

				// Catch-up to current position
				if ($pos > $lastPos)
				{
					$parentNode->insertBefore(
						$dom->createTextNode(
							substr($node->textContent, $lastPos, $pos - $lastPos)
						),
						$node
					);
				}
				$lastPos = $pos + strlen($m[0][0]);

				// Remove the offset data from the array, keep only the content of captures so that
				// $_m contains the same data that preg_match() or preg_replace() would return
				$_m = [];
				foreach ($m as $capture)
				{
					$_m[] = $capture[0];
				}

				// Get the replacement for this token
				$replacement = $fn($_m, $node);

				if ($replacement[0] === 'expression')
				{
					// Expressions are evaluated in a <xsl:value-of/> node
					$parentNode
						->insertBefore(
							$dom->createElementNS(self::XMLNS_XSL, 'xsl:value-of'),
							$node
						)
						->setAttribute('select', $replacement[1]);
				}
				elseif ($replacement[0] === 'passthrough')
				{
					// Passthrough token, replace with <xsl:apply-templates/>
					$parentNode->insertBefore(
						$dom->createElementNS(self::XMLNS_XSL, 'xsl:apply-templates'),
						$node
					);
				}
				else
				{
					// Literal replacement
					$parentNode->insertBefore($dom->createTextNode($replacement[1]), $node);
				}
			}

			// Append the rest of the text
			$text = substr($node->textContent, $lastPos);
			if ($text > '')
			{
				$parentNode->insertBefore($dom->createTextNode($text), $node);
			}

			// Now remove the old text node
			$parentNode->removeChild($node);
		}

		return self::saveTemplate($dom);
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
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
		}

		$dom = $node->ownerDocument;
		$dom->formatOutput = true;

		$docXml = self::innerXML($dom->documentElement);
		$docXml = trim(str_replace("\n  ", "\n", $docXml));

		$nodeHtml = htmlspecialchars(trim($dom->saveXML($node)));
		$docHtml  = htmlspecialchars($docXml);

		// Enclose the node's representation in our hilighting HTML
		$html = str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);

		// Remove the unique token from HTML and from the node
		if ($node instanceof DOMAttr)
		{
			$node->value = substr($node->value, 0, -strlen($uniqid));
			$html = str_replace($uniqid, '', $html);
		}
		elseif ($node instanceof DOMElement)
		{
			$node->removeAttribute($uniqid);
			$html = str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
			$html = str_replace($uniqid, '', $html);
		}

		return $html;
	}

	/**
	* Get the regexp used to remove meta elements from the intermediate representation
	*
	* @param  array  $templates
	* @return string
	*/
	public static function getMetaElementsRegexp(array $templates)
	{
		$exprs = [];

		// Coalesce all templates and load them into DOM
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . implode('', $templates) . '</xsl:template>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$xpath = new DOMXPath($dom);

		// Collect the values of all the "match", "select" and "test" attributes of XSL elements
		$query = '//xsl:*/@*[contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
		{
			$exprs[] = $attribute->value;
		}

		// Collect the XPath expressions used in all the attributes of non-XSL elements
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (AVTHelper::parse($attribute->value) as $token)
			{
				if ($token[0] === 'expression')
				{
					$exprs[] = $token[1];
				}
			}
		}

		// Names of the meta elements
		$tagNames = [
			'e' => true,
			'i' => true,
			's' => true
		];

		// In the highly unlikely event the meta elements are rendered, we remove them from the list
		foreach (array_keys($tagNames) as $tagName)
		{
			if (isset($templates[$tagName]) && $templates[$tagName] !== '')
			{
				unset($tagNames[$tagName]);
			}
		}

		// Create a regexp that matches the tag names used as element names, e.g. "s" in "//s" but
		// not in "@s" or "$s"
		$regexp = '(\\b(?<![$@])(' . implode('|', array_keys($tagNames)) . ')(?!-)\\b)';

		// Now look into all of the expressions that we've collected
		preg_match_all($regexp, implode("\n", $exprs), $m);

		foreach ($m[0] as $tagName)
		{
			unset($tagNames[$tagName]);
		}

		if (empty($tagNames))
		{
			// Always-false regexp
			return '((?!))';
		}

		return '(<' . RegexpBuilder::fromList(array_keys($tagNames)) . '>[^<]*</[^>]+>)';
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
		$tagNames = [];

		// Prepare the XPath expression used for the element's name
		$expr = 'name()';

		// Identify "simple" tags, whose template is one element of the same name. Their template
		// can be replaced with a dynamic template shared by all the simple tags
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

		if (is_string($chars) && $chars !== '')
		{
			$expr = 'translate(' . $expr . ",'" . $chars . "','" . strtolower($chars) . "')";
		}

		// Prepare the common template
		$template = '<xsl:element name="{' . $expr . '}">'
		          . '<xsl:apply-templates/>'
		          . '</xsl:element>';

		// Replace the templates
		foreach ($tagNames as $tagName)
		{
			$templates[$tagName] = $template;
		}
	}
}