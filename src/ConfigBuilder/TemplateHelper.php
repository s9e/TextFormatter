<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use DOMDocument,
    DOMNodeList,
    DOMXPath,
    InvalidArgumentException,
    LibXMLError,
    RuntimeException,
    XSLTProcessor;

abstract class TemplateHelper
{
	/**
	* Check an XSL template for unsafe markup
	*
	* @todo Possible additions: unsafe <object> and <embed>
	*
	* @param  string      $template Content of the template. A root node is not required
	* @param  Tag         $tag      Tag that this template belongs to
	* @return bool|string           FALSE if safe, a string containing an error message otherwise
	*/
	static public function checkUnsafe($template, Tag $tag)
	{
		$dom = self::loadTemplate($template);

		if ($dom instanceof LibXMLError)
		{
			throw new InvalidArgumentException('Invalid XML in template: ' . $dom->message);
		}

		return self::checkUnsafeScriptTags($dom, $tag)
		    ?: self::checkUnsafeEventAttributes($dom, $tag)
		    ?: self::checkUnsafeStyleTags($dom, $tag)
		    ?: self::checkUnsafeStyleAttributes($dom, $tag)
		    ?: self::checkUnsafeURLAttributes($dom, $tag)
		    ?: self::checkDisableOutputEscaping($dom)
		    ?: false;
	}

	/**
	* Optimize a template
	*
	* @param  string $template Content of the template. A root node is not required
	* @return string           Optimized template
	*/
	static public function optimizeTemplate($template)
	{
		$dom = self::loadTemplate($template);

		// Save single-space nodes then reload the template without whitespace
		self::preserveSingleSpaces($dom);
		$dom->preserveWhiteSpace = false;
		$dom->normalizeDocument();

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
	* Check a template for script tags using user-supplied data
	*
	* Looks for <script> tags with a dynamic value in @src, or with any descendant that is a
	* <xsl:value-of>, <xsl:attribute> or <xsl:apply-templates> node.
	*
	* @param  DOMDocument $dom xsl:template node
	* @param  Tag         $tag Tag that this template belongs to
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkUnsafeScriptTags(DOMDocument $dom, Tag $tag)
	{
		$xpath = new DOMXPath($dom);

		$scriptNodes = $xpath->query(
			  '//*[translate(local-name(), "SCRIPT", "script") = "script"]'
			. '|'
			. '//xsl:element[translate(@name, "SCRIPT", "script") = "script"]'
		);

		foreach ($scriptNodes as $scriptNode)
		{
			// <script><xsl:apply-templates/></script>
			if ($xpath->evaluate('count(.//xsl:apply-templates)', $scriptNode))
			{
				return 'The template contains a <script> tag that lets unfiltered data through';
			}

			// <script src="{@foo}">
			if ($xpath->evaluate('count(@*[translate(local-name(), "SRC", "src") = "src"][contains(., "{")])', $scriptNode))
			{
				return 'The template contains a <script> tag with a "src" attribute that uses user-supplied data';
			}

			// <script><xsl:attribute name="src">
			// <script><xsl:if><xsl:attribute name="src">
			if ($xpath->evaluate('count(.//xsl:attribute[translate(@name, "SRC", "src") = "src"])', $scriptNode))
			{
				return 'The template contains a <script> tag with a "src" attribute generated dynamically';
			}

			// <script><xsl:copy-of select="@src"/>
			if ($xpath->evaluate('count(.//xsl:copy-of)', $scriptNode))
			{
				return 'The template contains a <script> tag with a <xsl:copy-of> descendant';
			}

			// <script><xsl:value-of select="@foo"/>
			$valueOfNodes = $xpath->query('.//xsl:value-of/@select', $scriptNode);
			if (self::usesUnsafeAttribute($valueOfNodes, $tag, 'JS'))
			{
				return 'The template uses unfiltered or improperly filtered attributes inside of a <script> tag';
			}
		}
	}

	/**
	* Check a template for style tags using user-supplied data
	*
	* @param  DOMDocument $dom xsl:template node
	* @param  Tag         $tag Tag that this template belongs to
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkUnsafeStyleTags(DOMDocument $dom, Tag $tag)
	{
		$xpath = new DOMXPath($dom);

		$styleNodes = $xpath->query(
			  '//*[translate(local-name(), "STYLE", "style") = "style"]'
			. '|'
			. '//xsl:element[translate(@name, "STYLE", "style") = "style"]'
		);

		foreach ($styleNodes as $styleNode)
		{
			// <style><xsl:apply-templates/></style>
			if ($xpath->evaluate('count(.//xsl:apply-templates)', $styleNode))
			{
				return 'The template contains a <style> tag that lets unfiltered data through';
			}

			// <style><xsl:value-of select="@foo"/>
			$attrs = $xpath->query('.//xsl:value-of/@select');
			if (self::usesUnsafeAttribute($attrs, $tag, 'CSS'))
			{
				return 'The template uses unfiltered or improperly filtered attributes inside of a <style> tag';
			}
		}
	}

	/**
	* Check a template for style attributes using user-supplied data
	*
	* @param  DOMDocument $dom xsl:template node
	* @param  Tag         $tag Tag that this template belongs to
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkUnsafeStyleAttributes(DOMDocument $dom, Tag $tag)
	{
		$xpath = new DOMXPath($dom);

		// <b style="color:{@foo}">
		$attrs = $xpath->query('//@*[translate(local-name(), "STYLE", "style") = "style"]');
		if (self::usesUnsafeAttribute($attrs, $tag, 'CSS'))
		{
			return "The template uses unfiltered or improperly filtered attributes inside of a 'style' attribute";
		}

		$attrNodes = $xpath->query('//xsl:attribute[translate(@name, "STYLE", "style") = "style"]');
		foreach ($attrNodes as $attrNode)
		{
			// <b><xsl:attribute name="style"><xsl:apply-templates/>
			if ($xpath->evaluate('count(.//xsl:apply-templates)', $attrNode))
			{
				return "The template contains a dynamically generated 'style' attribute that lets unfiltered data through";
			}

			// <b><xsl:attribute name="style"><xsl:value-of select="@foo"/>
			$valueOfNodes = $xpath->query('.//xsl:value-of/@select', $attrNode);
			if (self::usesUnsafeAttribute($valueOfNodes, $tag, 'CSS'))
			{
				return "The template uses unfiltered or improperly filtered attributes inside of a dynamically generated 'style' attribute";
			}
		}
	}

	/**
	* Check a template for attributes expecting a URL and using user-supplied data
	*
	* @param  DOMDocument $dom xsl:template node
	* @param  Tag         $tag Tag that this template belongs to
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkUnsafeURLAttributes(DOMDocument $dom, Tag $tag)
	{
		/**
		* @var array List of attribute names that expect a valid URL
		* @link http://dev.w3.org/html5/spec/section-index.html#attributes-1
		*/
		$urlAttributes = array(
			'action',
			'cite',
			'data',
			'formaction',
			'href',
			'manifest',
			'poster',
			'src'
		);

		$xpath = new DOMXPath($dom);

		// Test for <a href="{foo}">
		$tests = array();
		foreach ($urlAttributes as $attrName)
		{
			$tests[] = 'translate(local-name(), "' . strtoupper($attrName) . '", "' . $attrName . '") = "' . $attrName . '"';
		}

		$attrs = $xpath->query('//@*[contains(., "{")][' . implode(' or ', $tests) . ']');
		if (self::usesUnsafeAttribute($attrs, $tag, 'URL'))
		{
			return 'The template uses unfiltered or improperly filtered attributes where a valid URL is expected';
		}

		// Test for <a><xsl:attribute name="href">
		$tests = array();
		foreach ($urlAttributes as $attrName)
		{
			$tests[] = 'translate(@name, "' . strtoupper($attrName) . '", "' . $attrName . '") = "' . $attrName . '"';
		}

		$attrNodes = $xpath->query('//xsl:attribute[' . implode(' or ', $tests) . ']');
		foreach ($attrNodes as $attrNode)
		{
			// <a><xsl:attribute name="href"><xsl:apply-templates/>
			if ($xpath->evaluate('count(.//xsl:apply-templates)', $attrNode))
			{
				return 'The template contains a dynamically generated attribute that expects a valid URL but lets unfiltered data through';
			}

			// <a><xsl:attribute name="href"><xsl:value-of select="@foo"/>
			$valueOfNodes = $xpath->query('.//xsl:value-of/@select', $attrNode);
			if (self::usesUnsafeAttribute($valueOfNodes, $tag, 'URL'))
			{
				return 'The template uses unfiltered or improperly filtered attributes inside of a dynamically generated attribute that expects a valid URL';
			}
		}
	}

	/**
	* Test whether a node uses the value of a attribute that isn't properly filtered
	*
	* @param  DOMNodeList $attrs   List of attributes to check
	* @param  Tag         $tag     Tag that this template belongs to
	* @param  string      $context Context in which the attributes are used: either JS, CSS or URL
	* @return bool
	*/
	static protected function usesUnsafeAttribute(DOMNodeList $attrs, Tag $tag, $context)
	{
		$methodName = 'isSafeIn' . $context;

		foreach ($attrs as $attr)
		{
			preg_match_all('#@([a-z_0-9\\-]+)#', $attr->textContent, $m);

			foreach ($m[1] as $attrName)
			{
				if (!$tag->attributes->exists($attrName))
				{
					// The template uses an attribute that is not defined, so we'll consider it
					// unsafe. It also covers the use of @*[name()="foo"]
					return true;
				}

				$attribute = $tag->attributes->get($attrName);

				// Test the attribute with the configured isSafeIn* method
				if (!call_user_func(array('self', $methodName), $attribute))
				{
					// Not safe
					return true;
				}
			}
		}
	}

	/**
	* Check a template for any tag using @disable-output-escaping
	*
	* @param  DOMDocument $dom xsl:template node
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkDisableOutputEscaping(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);

		if ($xpath->evaluate('count(//@disable-output-escaping)'))
		{
			return "The template contains a 'disable-output-escaping' attribute";
		}
	}

	/**
	* Check a template for any tag with a javascript event attribute using dynamic data
	*
	* @param  DOMDocument $dom xsl:template node
	* @param  Tag         $tag Tag that this template belongs to
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkUnsafeEventAttributes(DOMDocument $dom, Tag $tag)
	{
		$xpath = new DOMXPath($dom);

		// Check for <b onclick="{@foo}"/>
		// Note that it wrongly identifies <b onclick="{{@foo}}"/> as unsafe, but a false-positive
		// does not hurt
		$attrs = $xpath->query(
			'//@*[starts-with(translate(name(), "ON", "on"), "on")][contains(., "{")]'
		);

		if (self::usesUnsafeAttribute($attrs, $tag, 'JS'))
		{
			return 'The template uses unfiltered or improperly filtered attributes inside of an HTML event attribute';
		}

		// Check for <b><xsl:attribute name="onclick"><xsl:value-of .../></xsl:attribute></b>
		// and <b><xsl:attribute name="onclick"><xsl:apply-templates /></xsl:attribute></b>
		$attrs = $xpath->query(
			  '//xsl:attribute[starts-with(translate(@name, "ON", "on"), "on")]'
			. '//xsl:value-of/@select'
			. '|'
			. '//xsl:attribute[starts-with(translate(@name, "ON", "on"), "on")]'
			. '//xsl:apply-templates/@select'
		);

		if (self::usesUnsafeAttribute($attrs, $tag, 'JS'))
		{
			return 'The template uses unfiltered or improperly filtered attributes inside of a dynamically created HTML event attribute';
		}
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

		// Inline attributes
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

	/**
	* Evaluate whether an attribute is safe(ish) to use in a URL
	*
	* What we look out for:
	*  - javascript: URI
	*  - data: URI (high potential for XSS)
	*
	* @param  Attribute $attribute
	* @return bool
	*/
	static protected function isSafeInURL(Attribute $attribute)
	{
		// List of filters that make a value safe to be used as/in a URL
		$safeFilters = array(
			'#url',
			'urlencode',
			'rawurlencode',
			'#id',
			'#int',
			'#uint',
			'#float',
			'#range',
			'#number'
		);

		foreach ($safeFilters as $filter)
		{
			if ($attribute->filterChain->has($filter))
			{
				return true;
			}
		}

		// Test if that attribute uses a regexp
		if (isset($attribute->regexp)
		 && $attribute->filterChain->has('#regexp'))
		{
			// Test this regexp against a few possible vectors
			$unsafeValues = array(
				'javascript:',
				'Javascript:',
				'javaScript:',
				' javascript:',
				' Javascript:',
				' javaScript:',
				"\rjavaScript:",
				"\tjavaScript:",
				"\x00javaScript:",
				'data:',
				' data:',
				' DATA:'
			);

			foreach ($unsafeValues as $value)
			{
				if (preg_match($attribute->regexp, $value))
				{
					// Left an unsafe value through; This attribute is unsafe
					return false;
				}
			}

			// It left none of our bad values through, so we'll assume it is safe
			return true;
		}

		return false;
	}

	/**
	* Evaluate whether an attribute is safe(ish) to use in a CSS declaration
	*
	* What we look out for: anything that is not a number or a color. Anything else has security
	* implications:
	*  - MSIE's "behavior" extension can execute Javascript
	*  - Mozilla's -moz-binding
	*  - complex CSS can be used for phishing
	*  - javascript: and data: URI in background images
	*
	* @param  Attribute $attribute
	* @return bool
	*/
	static protected function isSafeInCSS(Attribute $attribute)
	{
		// List of filters that make a value safe to be used as/in CSS
		$safeFilters = array(
			'#int',
			'#uint',
			'#float',
			'#color',
			'#range',
			'#number'
		);

		foreach ($safeFilters as $filter)
		{
			if ($attribute->filterChain->has($filter))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Evaluate whether an attribute is safe(ish) to use in Javascript context
	*
	* What we look out for: anything that is not a number.
	*
	* @param  Attribute $attribute
	* @return bool
	*/
	static protected function isSafeInJS(Attribute $attribute)
	{
		// List of filters that make a value safe to be used in a script
		$safeFilters = array(
			'#int',
			'#uint',
			'#float',
			'#range',
			'#number'
		);

		foreach ($safeFilters as $filter)
		{
			if ($attribute->filterChain->has($filter))
			{
				return true;
			}
		}

		return false;
	}
}