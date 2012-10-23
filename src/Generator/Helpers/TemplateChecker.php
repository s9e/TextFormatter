<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator\Helpers;

use DOMAttr;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMNodeList;
use DOMXPath;
use InvalidArgumentException;
use RuntimeException;
use XSLTProcessor;
use s9e\TextFormatter\Generator\Items\Attribute;
use s9e\TextFormatter\Generator\Items\Tag;
use s9e\TextFormatter\Generator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Generator\Exceptions\UnsafeTemplateException;

/**
* Check individual templates for unsafe markup
*/
abstract class TemplateChecker
{
	/**
	* Check an XSL template for unsafe markup
	*
	* @todo Possible additions: unsafe <object> and <embed>
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

		$DOMXPath = new DOMXPath(TemplateHelper::loadTemplate($template));

		self::checkFixedSrcElements($DOMXPath);
		self::checkDisableOutputEscaping($DOMXPath);
		self::checkCopyElements($DOMXPath);
		self::checkUnsafeContent($DOMXPath, $tag);
		self::checkPHPTags($DOMXPath);
	}

	/**
	* Check elements whose src attribute should never be completely dynamic, such as <script>
	*
	* @param DOMXPath $DOMXPath DOMXPath associated with the template being checked
	*/
	protected static function checkFixedSrcElements(DOMXPath $DOMXPath)
	{
		$elements = array(
			'embed'  => 'src',
			'iframe' => 'src',
			'object' => 'data',
			'script' => 'src'
		);

		foreach ($DOMXPath->query('//*') as $node)
		{
			if ($node->namespaceURI === 'http://www.w3.org/1999/XSL/Transform'
			 && $node->localName    === 'element')
			{
				// We have a <xsl:element>
				$elName = $node->getAttribute('name');
			}
			else
			{
				// This is a static element, e.g. <script>, <iframe>, etc...
				$elName = $node->localName;
			}

			// Normalize the element name
			$elName = strtolower(trim($elName));

			if (!isset($elements[$elName]))
			{
				// Not one of the elements we're looking for
				continue;
			}

			// Grab the name of this element's attribute that contains an URL
			$attrName = $elements[$elName];

			if ($node->localName !== 'element')
			{
				// This is a static element, check for static attributes
				foreach ($node->attributes as $attribute)
				{
					if (strtolower($attribute->localName) === $attrName
					 && preg_match('#^\\s*\\{#', $attribute->nodeValue))
					{
						throw new UnsafeTemplateException("The template contains a '" . $elName . "' element with a non-fixed URL", $node);
					}
				}
			}

			// Search for a generated attribute that uses dynamic content
			$xpath = './/xsl:attribute[.//xsl:value-of or .//xsl:apply-templates]';
			foreach ($DOMXPath->query($xpath, $node) as $attributeElement)
			{
				$name = $attributeElement->getAttribute('name');

				if (trim(strtolower($name)) !== $attrName)
				{
					continue;
				}

				// Reject any src attribute that doesn't start with a non-whitespace text node
				if ($attributeElement->firstChild->nodeType !== XML_TEXT_NODE
				 || !preg_match('#\\S#', $attribute->firstChild->textContent))
				{
					$dynamic = ($node->localName === 'element')
					         ? "dynamically generated "
					         : '';

					throw new UnsafeTemplateException('The template contains a ' . $dynamic . "'" . $elName . "' element with a dynamically generated '" . $name . "' attribute that does not use a fixed URL", $node);
				}
			}
		}
	}

	/**
	* Check for <xsl:copy/> elements
	*
	* @param DOMXPath $DOMXPath DOMXPath associated with the template being checked
	*/
	protected static function checkCopyElements(DOMXPath $DOMXPath)
	{
		$node = $DOMXPath->query('//xsl:copy')->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("Cannot assess the safety of an 'xsl:copy' element", $node);
		}
	}

	/**
	* Check a template for any tag using @disable-output-escaping
	*
	* @param DOMXPath $DOMXPath DOMXPath associated with the template being checked
	*/
	protected static function checkDisableOutputEscaping(DOMXPath $DOMXPath)
	{
		$node = $DOMXPath->query('//@disable-output-escaping')->item(0);

		if ($node)
		{
			throw new UnsafeTemplateException("The template contains a 'disable-output-escaping' attribute", $node);
		}
	}

	/**
	* Check for improperly filtered content used in HTML tags
	*
	* @param DOMXPath $DOMXPath DOMXPath associated with the template being checked
	* @param Tag      $tag      Tag that this template belongs to
	*/
	protected static function checkUnsafeContent(DOMXPath $DOMXPath, Tag $tag = null)
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
		foreach ($DOMXPath->query('//* | //@*') as $node)
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
						throw new UnsafeTemplateException("Cannot assess 'xsl:" . $node->localName . "' name '" . $matchName . "'", $node);
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
						throw new UnsafeTemplateException("Cannot assess 'xsl:copy-of' select expression '" . $expr . "' to be safe", $node);
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
					self::checkUnsafeExpression($DOMXPath, $node, $expr, $contentType, $tag);
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

					self::checkUnsafeDescendants($DOMXPath, $node, $tag, $contentType, $predicate);
				}
			}
		}
	}

	/**
	* Check the descendants of given node
	*
	* @param DOMXPath   $DOMXPath    DOMXPath associated with the template being checked
	* @param DOMElement $element     Context node
	* @param Tag        $tag         Owner tag of this template
	* @param string     $contentType Content type (CSS, JS, etc...)
	* @param string     $predicate   Extra predicate
	*/
	protected static function checkUnsafeDescendants(DOMXPath $DOMXPath, DOMElement $element, Tag $tag, $contentType, $predicate)
	{
		// <script><xsl:value-of/></script>
		$xpath = './/xsl:value-of[@select]' . $predicate;
		foreach ($DOMXPath->query($xpath, $element) as $valueOf)
		{
			self::checkUnsafeExpression(
				$DOMXPath,
				$valueOf,
				$valueOf->getAttribute('select'),
				$contentType,
				$tag
			);
		}

		// <script><xsl:apply-templates/></script>
		// <script><xsl:apply-templates select="foo"/></script>
		$xpath = './/xsl:apply-templates' . $predicate;
		$applyTemplates = $DOMXPath->query($xpath, $element)->item(0);

		if ($applyTemplates)
		{
			if ($applyTemplates->hasAttribute('select'))
			{
				$msg = "Cannot assess the safety of 'xsl:apply-templates' select expression '" . $applyTemplates->getAttribute('select') . "'";
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
	* @param DOMXPath $DOMXPath DOMXPath associated with the template being checked
	* @param DOMNode  $node     Node being checked
	*/
	protected static function checkUnsafeContext(DOMXPath $DOMXPath, DOMNode $node)
	{
		if ($DOMXPath->query('ancestor::xsl:for-each', $node)->length)
		{
			throw new UnsafeTemplateException("Cannot evaluate context node due to 'xsl:for-each'", $node);
		}
	}

	/**
	* Test whether the template contains a <?php tag
	*
	* NOTE: PHP tags have no effect in templates, they are removed on the remote chance of being
	*       used as a vector of intrusion, for example if a template is saved in a publicly
	*       accessible file that the webserver is somehow configured to process as PHP
	*
	* @param DOMXPath $DOMXPath DOMXPath associated with the template being checked
	*/
	protected static function checkPHPTags(DOMXPath $DOMXPath)
	{
		$xpath = '//processing-instruction()["php" = translate(name(),"HP","hp")]';
		$nodes = $DOMXPath->query($xpath);

		if ($nodes->length)
		{
			throw new UnsafeTemplateException('PHP tags are not allowed', $nodes->item(0));
		}
	}

	/**
	* Check the safety of an XPath expression
	*
	* @param DOMXPath $DOMXPath    DOMXPath associated with the template being checked
	* @param DOMNode  $node        Context node
	* @param string   $expr        Expression to be checked
	* @param string   $contentType Content type
	* @param Tag      $tag         Tag that this template belongs to
	*/
	protected static function checkUnsafeExpression(DOMXPath $DOMXPath, DOMNode $node, $expr, $contentType, Tag $tag)
	{
		// We don't even try to assess its safety if it's not a single attribute value
		if (!preg_match('#^@\\s*([a-z_0-9\\-]+)$#Di', $expr, $m))
		{
			throw new UnsafeTemplateException("Cannot assess the safety of XPath expression '" . $expr . "'", $node);
		}

		self::checkUnsafeContext($DOMXPath, $node);

		$attrName = $m[1];

		if (!$tag->attributes->exists($attrName))
		{
			// The template uses an attribute that is not defined, so we'll consider it unsafe
			throw new UnsafeTemplateException("Undefined attribute '" . $attrName . "'", $node);
		}

		$attribute = $tag->attributes->get($attrName);

		// Test the attribute with the configured isSafeIn* method
		if (!call_user_func(array('self', 'isSafeIn' . $contentType), $attribute))
		{
			// Not safe
			throw new UnsafeTemplateException("Attribute '" . $attrName . "' is not properly filtered to be used in " . $contentType, $node);
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
	protected static function isSafeInURL(Attribute $attribute)
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
			if ($attribute->filterChain->contains($filter))
			{
				return true;
			}
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
			'#range',
			'#number',
			'#simpletext'
		);

		foreach ($safeFilters as $filter)
		{
			if ($attribute->filterChain->contains($filter))
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

		foreach ($safeFilters as $filter)
		{
			if ($attribute->filterChain->contains($filter))
			{
				return true;
			}
		}

		return false;
	}
}