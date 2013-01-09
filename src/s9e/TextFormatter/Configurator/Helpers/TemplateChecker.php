<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use XSLTProcessor;
use s9e\TextFormatter\Configurator\Items\Attribute;
use s9e\TextFormatter\Configurator\Items\CallbackPlaceholder;
use s9e\TextFormatter\Configurator\Items\Tag;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Exceptions\UnsafeTemplateException;

/**
* Check individual templates for unsafe markup
*
* NOTE: this class expects the input to have been normalized by TemplateOptimizer
*/
abstract class TemplateChecker
{
	/**
	* Check an XSL template for unsafe markup
	*
	* @todo Possible additions: unsafe <object> and <embed>
	* @todo Investigate <embed name="foo.swf" src="foo.swf"/>, also <param name="movie" value="foo.swf"/>
	* @todo unsafe: <b onclick='alert("{@foo}")'/> even with json_encode() -- safe: <b onclick='alert({@foo})'/> -- IOW ensure the JSON thing is not enclosed in quotes -- also unsafe: onclick="/*{@foo}"
	* @todo consider any variable safe? <xsl:value-of select="$foo"/>
	*
	* @param  string $template Content of the template. A root node is not required
	* @param  Tag    $tag      Tag that this template belongs to
	* @return void
	*/
	public static function checkUnsafe($template, Tag $tag = null)
	{
		if (!isset($tag))
		{
			$tag = new Tag;
		}

		$xpath = new DOMXPath(TemplateHelper::loadTemplate($template));

		self::checkFixedUrlAttributes($xpath);
		self::checkDisableOutputEscaping($xpath);
		self::checkCopyElements($xpath);
		self::checkUnsafeContent($xpath, $tag);
		self::checkPHPTags($xpath);
		self::checkAttributeSets($xpath);
	}

	/**
	* Check URL-type attributes that should never be completely dynamic, such as <script src>
	*
	* NOTE: this will fail to recognize src="http://foo{'@'}{@textstuff}" because 'foo' will be
	*       identified as the host part of the URL whereas it's actually a credential
	*
	* @param DOMXPath $xpath DOMXPath associated with the template being checked
	*/
	protected static function checkFixedUrlAttributes(DOMXPath $xpath)
	{
		$attributes = array(
			'//iframe | //script'
				=> array('src', null),

			'//embed'
				=> array('src', '@allowscriptaccess = "never"'),

			'//object'
				=> array(
					'data',
					'param'
					. '[translate(@name, "ACEILOPRSTW", "aceiloprstw") = "allowscriptaccess"]'
					. '[@value = "never"]'
				),

			'//param[translate(@name, "MOVIE", "movie") = "movie"]'
				=> array(
					'value',
					'parent::object/param'
					. '[translate(@name, "ACEILOPRSTW", "aceiloprstw") = "allowscriptaccess"]'
					. '[@value = "never"]'
				)
		);

		// Match protocol:// or // followed optional "user:pass@" credentials followed by an
		// alphanumerical character
		$regexp = '#^(?:[a-z0-9]+:)?//\\w#i';

		foreach ($attributes as $elementQuery => $pair)
		{
			list($attrName, $exceptionQuery) = $pair;

			foreach ($xpath->query($elementQuery) as $element)
			{
				/**
				* @todo move that to a new method dedicated to checking Flash stuff
				* @todo what about multiple definitions of the "allowscriptaccess" param? what happens if one says "never" and two other say "always"
				*/
				// Test whether this case allows some exceptions
				if (isset($exceptionQuery)
				 && $xpath->evaluate('boolean(' . $exceptionQuery . ')', $element))
				{
					continue;
				}

				// Test the element's attribute
				if ($element->hasAttribute($attrName))
				{
					$attrValue = $element->getAttribute($attrName);

					// Test whether this value matches our regexp, but only if it has a dynamic
					// component (the simple check for a curly bracket could theorically produce
					// some rare false positive, but it doesn't matter)
					if (strpos($attrValue, '{') !== false
					 && !preg_match($regexp, $attrValue))
					{
						throw new UnsafeTemplateException("The template contains a '" . $element->nodeName . "' element with a non-fixed URL attribute '" . $attrName . "'", $element);
					}
				}

				// Match non-XSL ancestors
				$ancestor = 'ancestor::*[namespace-uri()!="http://www.w3.org/1999/XSL/Transform"]';

				// Count the number of non-XSL ancestors
				$cnt = $xpath->evaluate('count(' . $ancestor . ')', $element);

				// Match <xsl:attribute/> descendants that don't have more non-XSL ancestors than
				// our context node
				$attributeQuery
					= './/xsl:attribute'
					. '[not(' . $ancestor . '[count(' . $ancestor. ')>' . $cnt . '])]'
					. '[translate(@name,"' . strtoupper($attrName) . '","' . $attrName . '")="' . $attrName . '"]';

				foreach ($xpath->query($attributeQuery, $element) as $attribute)
				{
					if ($attribute->firstChild->nodeType !== XML_TEXT_NODE
					 || !preg_match($regexp, $attribute->firstChild->textContent))
					{
						throw new UnsafeTemplateException("The template contains a '" . $element->nodeName . "' element with a dynamically generated '" . $attrName . "' attribute that does not use a fixed URL", $element);
					}
				}
			}
		}
	}

	/**
	* Check for <xsl:copy/> elements
	*
	* @param DOMXPath $xpath DOMXPath associated with the template being checked
	*/
	protected static function checkCopyElements(DOMXPath $xpath)
	{
		$node = $xpath->query('//xsl:copy')->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("Cannot assess the safety of an '" . $node->nodeName . "' element", $node);
		}
	}

	/**
	* Check a template for any tag using @disable-output-escaping
	*
	* @param DOMXPath $xpath DOMXPath associated with the template being checked
	*/
	protected static function checkDisableOutputEscaping(DOMXPath $xpath)
	{
		$node = $xpath->query('//@disable-output-escaping')->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("The template contains a 'disable-output-escaping' attribute", $node);
		}
	}

	/**
	* Check for improperly filtered content used in HTML tags
	*
	* @param DOMXPath $xpath DOMXPath associated with the template being checked
	* @param Tag      $tag   Tag that this template belongs to
	*/
	protected static function checkUnsafeContent(DOMXPath $xpath, Tag $tag = null)
	{
		$checkElements = array(
			'/^style$/i'  => 'CSS',
			'/^script$/i' => 'JS'
		);

		$checkAttributes = array(
			'/^style$/i'      => 'CSS',
			// onclick, onmouseover, etc...
			'/^on/i'          => 'JS',
			'/^action$/i'     => 'URL',
			'/^cite$/i'       => 'URL',
			'/^data$/i'       => 'URL',
			'/^formaction$/i' => 'URL',
			'/^href$/i'       => 'URL',
			'/^manifest$/i'   => 'URL',
			'/^poster$/i'     => 'URL',
			// Covers "src" as well as non-standard attributes "dynsrc", "lowsrc"
			'/src$/i'         => 'URL'
		);

		// NOTE: this XPath query will return attributes from XSL nodes, but there should be no
		//       false-positives from them, so we don't have to filter them out
		foreach ($xpath->query('//* | //@*') as $node)
		{
			/**
			* @var array XPath expressions to be checked
			*/
			$checkExpr = array();

			if ($node->namespaceURI === 'http://www.w3.org/1999/XSL/Transform')
			{
				if ($node->localName === 'attribute'
				 || $node->localName === 'element')
				{
					// <xsl:attribute/> or <xsl:element/>
					$matchName = trim($node->getAttribute('name'));
					$matchList = ($node->localName === 'attribute')
					           ? $checkAttributes
					           : $checkElements;

					// Ensure no shenanigans such as dynamic names, e.g. <xsl:element name="{foo}"/>
					if (!preg_match('#^([a-z_0-9\\-]+)$#Di', $matchName))
					{
						throw new UnsafeTemplateException("Cannot assess '" . $node->nodeName . "' name '" . $matchName . "'", $node);
					}
				}
				elseif ($node->localName === 'copy-of')
				{
					$expr = trim($node->getAttribute('select'));

					// Replace <xsl:copy-of select="@ foo"/> with <xsl:copy-of select="@foo"/>
					$expr = preg_replace('#^@\\s+#', '@', $expr);

					if (!preg_match('#^@([a-z_0-9\\-]+)$#Di', $expr, $m))
					{
						// Reject anything that is not a copy of an attribute
						throw new UnsafeTemplateException("Cannot assess '" . $node->nodeName . "' select expression '" . $expr . "' to be safe", $node);
					}

					$matchName = $m[1];
					$matchList = $checkAttributes;

					$checkExpr[] = $expr;
				}
				else
				{
					// <xsl:*/> and theorically even the attribute in <b xsl:foo=""/>
					continue;
				}
			}
			elseif ($node instanceof DOMAttr)
			{
				$matchName = $node->localName;
				$matchList = $checkAttributes;

				preg_match_all('#(\\{+)([^}]+)\\}#', $node->nodeValue, $matches, PREG_SET_ORDER);

				foreach ($matches as $m)
				{
					// If the number of { is odd, it means the expression will be evaluated
					if (strlen($m[1]) % 2)
					{
						$checkExpr[] = $m[2];
					}
				}
			}
			else
			{
				$matchName = $node->localName;
				$matchList = $checkElements;
			}

			// Test whether our node matches any entry on our matchlist
			foreach ($matchList as $regexp => $contentType)
			{
				if (!preg_match($regexp, $matchName))
				{
					// Nope, move on to the next entry
					continue;
				}

				// Check expressions from <xsl:copy-of select="{@onclick}"/> and
				// <b onmouseover="this.title='{@title}';this.style.backgroundColor={@color}"/>
				foreach ($checkExpr as $expr)
				{
					self::checkUnsafeExpression($xpath, $node, $expr, $contentType, $tag);
				}

				// Check for unsafe descendants if our node is an element (not an attribute)
				if ($node instanceof DOMElement)
				{
					// If current node is not an xsl:attribute element, we exclude descendants
					// with an xsl:attribute ancestor so that content such as:
					//   <script><xsl:attribute name="id"><xsl:value-of/></xsl:attribute></script>
					// would not trigger a false-positive due to the presence of an xsl:value-of
					// element in a <script>
					$predicate = ($node->localName === 'attribute')
					           ? ''
					           : '[not(ancestor::xsl:attribute)]';

					self::checkUnsafeDescendants($xpath, $node, $tag, $contentType, $predicate);
				}
			}
		}
	}

	/**
	* Check the descendants of given node
	*
	* @param DOMXPath   $xpath       DOMXPath associated with the template being checked
	* @param DOMElement $element     Context node
	* @param Tag        $tag         Owner tag of this template
	* @param string     $contentType Content type (CSS, JS, etc...)
	* @param string     $predicate   Extra predicate
	*/
	protected static function checkUnsafeDescendants(DOMXPath $xpath, DOMElement $element, Tag $tag, $contentType, $predicate)
	{
		// <script><xsl:value-of/></script>
		$query = './/xsl:value-of[@select]' . $predicate;
		foreach ($xpath->query($query, $element) as $valueOf)
		{
			self::checkUnsafeExpression(
				$xpath,
				$valueOf,
				$valueOf->getAttribute('select'),
				$contentType,
				$tag
			);
		}

		// <script><xsl:apply-templates/></script>
		// <script><xsl:apply-templates select="foo"/></script>
		$query = './/xsl:apply-templates' . $predicate;
		$applyTemplates = $xpath->query($query, $element)->item(0);

		if ($applyTemplates)
		{
			if ($applyTemplates->hasAttribute('select'))
			{
				$msg = "Cannot assess the safety of '" . $applyTemplates->nodeName . "' select expression '" . $applyTemplates->getAttribute('select') . "'";
			}
			elseif ($element->namespaceURI === 'http://www.w3.org/1999/XSL/Transform')
			{
				$msg = "A dynamically generated '" . $element->getAttribute('name') . "' " . $element->localName . ' lets unfiltered data through';
			}
			else
			{
				$msg = "A '" . $element->localName . "' element lets unfiltered data through";
			}

			throw new UnsafeTemplateException($msg, $applyTemplates);
		}
	}

	/**
	* Test whether the context of an element can be evaluated
	*
	* @param DOMXPath $xpath DOMXPath associated with the template being checked
	* @param DOMNode  $node  Node being checked
	*/
	protected static function checkUnsafeContext(DOMXPath $xpath, DOMNode $node)
	{
		$nodes = $xpath->query('ancestor::xsl:for-each', $node);
		if ($nodes->length)
		{
			throw new UnsafeTemplateException("Cannot evaluate context node due to '" . $nodes->item(0)->nodeName . "'", $node);
		}
	}

	/**
	* Test whether the template contains a <?php tag
	*
	* NOTE: PHP tags have no effect in templates, they are removed on the remote chance of being
	*       used as a vector of intrusion, for example if a template is saved in a publicly
	*       accessible file that the webserver is somehow configured to process as PHP
	*
	* @param DOMXPath $xpath DOMXPath associated with the template being checked
	*/
	protected static function checkPHPTags(DOMXPath $xpath)
	{
		$query = '//processing-instruction()["php" = translate(name(),"HP","hp")]';
		$nodes = $xpath->query($query);

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('PHP tags are not allowed', $nodes->item(0));
		}
	}

	/**
	* Test whether the template contains an <xsl:attribute-set/>
	*
	* Templates are checked outside of their stylesheet, which means we don't have access to the
	* <xsl:attribute-set/> declarations and we can't easily test them. Attribute sets are fairly
	* uncommon and there's little incentive to use them in small stylesheets, so we'll just disable
	* them
	*
	* @param DOMXPath $xpath DOMXPath associated with the template being checked
	*/
	protected static function checkAttributeSets(DOMXPath $xpath)
	{
		$query = '//@use-attribute-sets';
		$nodes = $xpath->query($query);

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('Cannot assess the safety of attribute sets', $nodes->item(0));
		}
	}

	/**
	* Check the safety of an XPath expression
	*
	* @param DOMXPath $xpath       DOMXPath associated with the template being checked
	* @param DOMNode  $node        Context node
	* @param string   $expr        Expression to be checked
	* @param string   $contentType Content type
	* @param Tag      $tag         Tag that this template belongs to
	*/
	protected static function checkUnsafeExpression(DOMXPath $xpath, DOMNode $node, $expr, $contentType, Tag $tag)
	{
		// We don't even try to assess its safety if it's not a single attribute value
		if (!preg_match('#^@\\s*([a-z_0-9\\-]+)$#Di', $expr, $m))
		{
			throw new UnsafeTemplateException("Cannot assess the safety of XPath expression '" . $expr . "'", $node);
		}

		self::checkUnsafeContext($xpath, $node);

		$attrName = $m[1];

		if (!$tag->attributes->exists($attrName))
		{
			// The template uses an attribute that is not defined, so we'll consider it unsafe
			throw new UnsafeTemplateException("Undefined attribute '" . $attrName . "'", $node);
		}

		$attribute = $tag->attributes->get($attrName);

		// Test the attribute with the configured isSafeIn* method
		if (call_user_func(array('self', 'isSafeIn' . $contentType), $attribute))
		{
			// Safe
			return;
		}

		// Deal with special cases
		if ($node instanceof DOMAttr)
		{
			if ($contentType === 'URL')
			{
				// Allowed match
				$match = 'mailto:{' . $expr . '}';
				if (substr($node->textContent, 0, strlen($match)) === $match)
				{
					// Safe
					return;
				}
			}
		}

		// Not safe
		throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly filtered to be used in " . $contentType, $node);
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
	protected static function isSafeInURL(Attribute $attribute)
	{
		// List of filters that make a value safe to be used as/in a URL
		$safeFilters = array(
			'#url',
			'urlencode',
			'rawurlencode',
			'#identifier',
			'#int',
			'#uint',
			'#float',
			'#range',
			'#number',
			/** @todo should probably ensure the regexp isn't something useless like /./ */
			'#regexp'
		);

		return self::hasSafeFilter($attribute, $safeFilters);
	}

	/**
	* Evaluate whether an attribute is safe(ish) to use in a CSS declaration
	*
	* What we look out for: anything that is not a number, a URL or a color. We also allow "simple"
	* text because it does not allow ":" or "(" so it cannot be used to set new CSS attributes.
	*
	* Raw text has security implications:
	*  - MSIE's "behavior" extension can execute JavaScript
	*  - Mozilla's -moz-binding
	*  - complex CSS can be used for phishing
	*  - javascript: and data: URI in background images
	*  - CSS expressions (MSIE only?) can execute JavaScript
	*
	* @param  Attribute $attribute
	* @return bool
	*/
	protected static function isSafeInCSS(Attribute $attribute)
	{
		// List of filters that make a value safe to be used as/in CSS
		$safeFilters = array(
			// URLs should be safe because characters ()'" are urlencoded
			'#url',
			'#int',
			'#uint',
			'#float',
			'#color',
			'#number',
			'#range',
			'#regexp',
			'#simpletext'
		);

		return self::hasSafeFilter($attribute, $safeFilters);
	}

	/**
	* Evaluate whether an attribute is safe(ish) to use in JavaScript context
	*
	* What we look out for: anything that is not a number or a URL. We allow "simple" text because
	* it is sometimes used in spoiler tags. #simpletext doesn't allow quotes or parentheses so it
	* has a low potential for exploit. The default #url filter urlencodes quotes and parentheses,
	* otherwise it could be a vector.
	*
	* @param  Attribute $attribute
	* @return bool
	*/
	protected static function isSafeInJS(Attribute $attribute)
	{
		// List of filters that make a value safe to be used in a script
		$safeFilters = array(
			// Those might see some usage
			'json_encode',
			'rawurlencode',
			'strtotime',
			'urlencode',
			// URLs should be safe because characters ()'" are urlencoded
			'#url',
			'#int',
			'#uint',
			'#float',
			'#range',
			'#number',
			'#simpletext'
		);

		return self::hasSafeFilter($attribute, $safeFilters);
	}

	/**
	* Return whether an attribute's filterChain contains a filter from given list
	*
	* @param  Attribute $attribute
	* @param  array     $safeFilters
	* @return bool
	*/
	protected static function hasSafeFilter(Attribute $attribute, array $safeFilters)
	{
		foreach ($attribute->filterChain as $filter)
		{
			$callback = $filter->getCallback();

			if ($callback instanceof CallbackPlaceholder)
			{
				$callback = $callback->asConfig();
			}

			if (in_array($callback, $safeFilters, true))
			{
				return true;
			}
		}

		return false;
	}
}