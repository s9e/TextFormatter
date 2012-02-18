<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use DOMAttr,
    DOMDocument,
    DOMElement,
    DOMNodeList,
    DOMXPath,
    InvalidArgumentException,
    LibXMLError,
    RuntimeException,
    XSLTProcessor;

abstract class TemplateChecker
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

		return self::checkUnsafeContent($dom, $tag)
		    ?: self::checkDisableOutputEscaping($dom)
		    ?: self::checkCopyElements($dom)
			// check for <xsl:element name="{concat('scr','ipt')}"/> or similar <xsl:attribute> tags
		    ?: self::checkSuspiciousDynamicElements($dom)
		    ?: false;
	}

	/**
	* Load a template into a DOMDocument
	*
	* Returns a DOMDocument on success, or a LibXMLError otherwise
	*
	* @param  string $template Content of the template. A root node is not required
	* @return DOMDocument|LibXMLError
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
	* Check a suspiciously dynamic elements such as <xsl:element name="{@foo}"/>
	*
	* @param  DOMDocument $dom xsl:template node
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkSuspiciousDynamicElements(DOMDocument $dom)
	{
		$DOMXPath = new DOMXPath($dom);

		$xpath = '//xsl:element[contains(@name, "{")]'
		       . '|'
		       . '//xsl:attribute[contains(@name, "{")]';

		foreach ($DOMXPath->query($xpath) as $node)
		{
			return "The template contains an '" . $node->nodeName . "' element with a dynamic @name";
		}
	}

	/**
	* Check for <xsl:copy/> elements
	*
	* @param  DOMDocument $dom xsl:template node
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkCopyElements(DOMDocument $dom)
	{
		$DOMXPath = new DOMXPath($dom);

		// Reject any <xsl:copy/> node
		if ($DOMXPath->query('//xsl:copy')->length)
		{
			return "The template contains an 'xsl:copy' element";
		}

		return false;
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
	* Check a improperly filtered content used in HTML tags
	*
	* @param  DOMDocument $dom xsl:template node
	* @param  Tag         $tag Tag that this template belongs to
	* @return bool|string      Error message if unsafe, FALSE otherwise
	*/
	static protected function checkUnsafeContent(DOMDocument $dom, Tag $tag)
	{
		$DOMXPath = new DOMXPath($dom);

		// What we check, sorted by content type
		$checkNodes = array(
			'CSS' => array(
				'style',
				'@style'
			),
			'JS' => array(
				'script',
				'@on*'
			),
			'URL' => array(
				'@action',
				'@cite',
				'@data',
				'@formaction',
				'@href',
				'@manifest',
				'@poster',
				// Covers "src" as well as non-standard attributes "dynsrc", "lowsrc"
				'@*src'
			)
		);

		foreach ($checkNodes as $contentType => $names)
		{
			$predicates = array();

			foreach ($names as $name)
			{
				if ($name[0] === '@')
				{
					$name = substr($name, 1);

					// Target dynamic attributes, e.g. <a href="{@url}">
					$staticTarget  = '@*[contains(., "{")]';
					$dynamicTarget = 'xsl:attribute';
				}
				else
				{
					$staticTarget  = '*';
					$dynamicTarget = 'xsl:element';
				}

				if ($name[0] === '*')
				{
					$name   = substr($name, 1);
					$format = 'substring(%1$s, string-length(%1$s) - string-length(%2$s)) = %2$s';
				}
				elseif (substr($name, -1) === '*')
				{
					$name   = substr($name, 0, -1);
					$format = 'starts-with(%1$s, %2$s)';
				}
				else
				{
					$format = '%1$s = %2$s';
				}

				// translate() allows us to match HTML tags in uppercase, e.g. <ScRiPt>
				// We use local-name() to match tags that would be namespaced to the HTML namespace,
				// e.g. <x:script xmlns:x="http://www.w3.org/1999/xhtml"> which may or may not work
				// in some browsers (erring on the safe side)
				$predicates[$staticTarget][] = sprintf(
					$format,
					'translate(local-name(),"' . strtoupper($name) . '","' . $name . '")',
					'"' . $name . '"'
				);

				// normalize-space() is used as a failsafe in case a faulty XSLT implementation
				// would allow <xsl:element name=" script ">
				$predicates[$dynamicTarget][] = sprintf(
					$format,
					'translate(normalize-space(@name),"' . strtoupper($name) . '","' . $name . '")',
					'"' . $name . '"'
				);

				if ($dynamicTarget === 'xsl:attribute')
				{
					// Look for copies of attributes via <xsl:copy-of/>
					$predicates['xsl:copy-of'][] = sprintf(
						$format,
						'normalize-space(@select)',
						'"@' . $name . '"'
					);
				}
			}

			// Build the XPath expression for each target type
			$exprs = array();
			foreach ($predicates as $target => $targetPredicates)
			{
				$exprs[$target] = '//' . $target . '[' . implode(' or ', $targetPredicates) . ']';
			}
print_r($exprs);
			// Test for <xsl:copy-of/> nodes
			foreach ($DOMXPath->query($exprs['xsl:copy-of']) as $node)
			{
				$unsafeMsg = self::checkUnsafeSelect($node, $tag, $contentType);

				if ($unsafeMsg)
				{
					return "The template contains a copy of the '" . trim($node->getAttribute('name'), ' @') . "' attribute that " . $unsafeMsg;
				}
			}
			unset($exprs['xsl:copy-of']);

			// Test for all other nodes
			$xpath = implode(' | ', $exprs);
			foreach ($DOMXPath->query($xpath) as $node)
			{
				$unsafeMsg = ($node instanceof DOMAttr)
				           ? self::checkUnsafeAttribute($node, $tag, $contentType)
				           : self::checkUnsafeElement($node, $tag, $contentType);

				if ($unsafeMsg)
				{
					if ($node instanceof DOMAttr)
					{
						// "'src' attribute"
						$targetDesc = "'" . $node->nodeName . "' attribute";
					}
					elseif ($node->namespaceURI === 'http://www.w3.org/1999/XSL/Transform')
					{
						// "dynamically generated 'src' attribute" (or element)
						$targetDesc = "dynamically generated '" . $node->getAttribute('name') . "' " . $node->localName;
					}
					else
					{
						// "'script' element"
						$targetDesc = "'" . $node->nodeName . "' element";
					}

					return 'The template contains a ' . $targetDesc . ' that ' . $unsafeMsg;
				}
			}
		}
	}

	/**
	* 
	*
	* @param  DOMElement  $element
	* @param  Tag         $tag
	* @param  string      $contentType
	* @return string|bool
	*/
	static protected function checkUnsafeAttribute(DOMAttr $attr, Tag $tag, $contentType)
	{
	}

	/**
	* 
	*
	* @param  DOMElement  $element
	* @param  Tag         $tag
	* @param  string      $contentType
	* @return string|bool
	*/
	static protected function checkUnsafeElement(DOMElement $element, Tag $tag, $contentType)
	{
		$DOMXPath = new DOMXPath($element->ownerDocument);

		// <script><xsl:value-of/></script>
		foreach ($DOMXPath->query('.//xsl:value-of[@select]', $element) as $node)
		{
			$unsafeMsg = self::checkUnsafeSelect($node, $tag, $contentType);

			if ($unsafeMsg)
			{
				return $unsafeMsg;
			}

			if ($DOMXPath->query('.//xsl:apply-templates', $node)->length)
			{
				return "contains an 'xsl:apply-templates' node that may let unfiltered data through";
			}
		}

		return false;
	}

	/**
	* 
	*
	* @param  DOMElement  $element
	* @param  Tag         $tag
	* @param  string      $contentType
	* @return string|bool
	*/
	static protected function checkUnsafeSelect(DOMElement $element, Tag $tag, $contentType)
	{
		$expr = trim($element->getAttribute('select'));

		// We don't even try to assess its safety if it's not a single attribute value
		if (!preg_match('#^@\\s*([a-z_0-9\\-]+)$#Di', $expr, $m))
		{
			return "contains a '" . $element->nodeName . "' element whose select expression '" . $expr . "' cannot be assessed to be safe";
		}

		$attrName = $m[1];

		if (!$tag->attributes->exists($attrName))
		{
			// The template uses an attribute that is not defined, so we'll consider it
			// unsafe. It also covers the use of @*[name()="foo"]
			return "uses an undefined '" . $attrName . "' attribute";
		}

		$attribute = $tag->attributes->get($attrName);

		// Test the attribute with the configured isSafeIn* method
		if (!call_user_func(array('self', $methodName), $attribute))
		{
			// Not safe
			return "uses the value of the '" . $attrName . "' attribute, which isn't properly filtered";
		}

		return false;
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
			'//@*[starts-with(translate(local-name(), "ON", "on"), "on")][contains(., "{")]'
		);

		if (self::usesUnsafeAttribute($attrs, $tag, 'JS'))
		{
			return 'The template uses unfiltered or improperly filtered attributes inside of an HTML event attribute';
		}

		// <b><xsl:attribute name="onclick"><xsl:value-of .../></xsl:attribute></b>
		$attrs = $xpath->query(
			  '//xsl:attribute[starts-with(translate(normalize-space(@name), "ON", "on"), "on")]'
			. '//xsl:value-of/@select'
		);

		if (self::usesUnsafeAttribute($attrs, $tag, 'JS'))
		{
			return 'The template uses unfiltered or improperly filtered attributes inside of a dynamically created HTML event attribute';
		}

		// <b><xsl:attribute name="onclick"><xsl:apply-templates /></xsl:attribute></b>
		$query = '//xsl:attribute[starts-with(translate(normalize-space(@name), "ON", "on"), "on")]'
		       . '//xsl:apply-templates';

		if ($xpath->evaluate('count(' . $query . ')'))
		{
			return 'The template contains an HTML event attribute that lets unfiltered data through';
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
		 && $attribute->filterChain->has('#regexp')
		 && preg_match('#^(.)\\^.*\\$\\1[a-z]*$#Dis', $attribute->regexp))
		{
			// Test this regexp against a few possible vectors
			$unsafeValues = array(
				'javascript:stuff',
				'Javascript:stuff',
				'javaScript:stuff',
				' javascript: stuff',
				' Javascript:stuff',
				' javaScript:stuff',
				"\rjavaScript:stuff",
				"\tjavaScript:stuff",
				"\x00javaScript:stuff",
				'data:stuff',
				' data:stuff',
				' DATA:stuff'
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
	* What we look out for: anything that is not a number, a URL or a color. We also allow "simple"
	* text because it does not allow ":" or "(" so it cannot be used to set new CSS attributes.
	*
	* Raw text has security implications:
	*  - MSIE's "behavior" extension can execute Javascript
	*  - Mozilla's -moz-binding
	*  - complex CSS can be used for phishing
	*  - javascript: and data: URI in background images
	*  - CSS expressions (MSIE only?) can execute Javascript
	*
	* @param  Attribute $attribute
	* @return bool
	*/
	static protected function isSafeInCSS(Attribute $attribute)
	{
		// List of filters that make a value safe to be used as/in CSS
		$safeFilters = array(
			// URLs should be safe because characters ()'" are urlencoded
			'#url',
			'#int',
			'#uint',
			'#float',
			'#color',
			'#range',
			'#number',
			'#simpletext'
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
	* What we look out for: anything that is not a number or a URL. We allow "simple" text because
	* it is sometimes used in spoiler tags. #simpletext doesn't allow quotes or parentheses so it
	* has a low potential for exploit. The default #url filter urlencodes quotes and parentheses,
	* otherwise it could be a vector.
	*
	* @param  Attribute $attribute
	* @return bool
	*/
	static protected function isSafeInJS(Attribute $attribute)
	{
		// List of filters that make a value safe to be used in a script
		$safeFilters = array(
			// URLs should be safe because characters ()'" are urlencoded
			'#url',
			'#int',
			'#uint',
			'#float',
			'#range',
			'#number',
			'#simpletext'
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