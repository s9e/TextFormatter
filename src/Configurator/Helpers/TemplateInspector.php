<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/**
* This class helps the RulesGenerator by analyzing a given template in order to answer questions
* such as "can this tag be a child/descendant of that other tag?" and others related to the HTML5
* content model.
*
* We use the HTML5 specs to determine which children or descendants should be allowed or denied
* based on HTML5 content models. While it does not exactly match HTML5 content models, it gets
* pretty close. We also use HTML5 "optional end tag" rules to create closeParent rules.
*
* Currently, this method does not evaluate elements created with <xsl:element> correctly, or
* attributes created with <xsl:attribute> and may never will due to the increased complexity it
* would entail. Additionally, it does not evaluate the scope of <xsl:apply-templates/>. For
* instance, it will treat <xsl:apply-templates select="LI"/> as if it was <xsl:apply-templates/>
*
* @link http://dev.w3.org/html5/spec/content-models.html#content-models
* @link http://dev.w3.org/html5/spec/syntax.html#optional-tags
* @see  /scripts/patchTemplateInspector.php
*/
class TemplateInspector
{
	/**
	* @var string[] allowChild bitfield for each branch
	*/
	protected $allowChildBitfields = [];

	/**
	* @var bool Whether elements are allowed as children
	*/
	protected $allowsChildElements = true;

	/**
	* @var bool Whether text nodes are allowed as children
	*/
	protected $allowsText = true;

	/**
	* @var string OR-ed bitfield representing all of the categories used by this template
	*/
	protected $contentBitfield = "\0";

	/**
	* @var string denyDescendant bitfield
	*/
	protected $denyDescendantBitfield = "\0";

	/**
	* @var DOMDocument Document containing the template
	*/
	protected $dom;

	/**
	* @var bool Whether this template contains any HTML elements
	*/
	protected $hasElements = false;

	/**
	* @var bool Whether this template renders non-whitespace text nodes at its root
	*/
	protected $hasRootText = false;

	/**
	* @var bool Whether this template should be considered a block-level element
	*/
	protected $isBlock = false;

	/**
	* @var bool Whether the template uses the "empty" content model
	*/
	protected $isEmpty = true;

	/**
	* @var bool Whether this template adds to the list of active formatting elements
	*/
	protected $isFormattingElement = false;

	/**
	* @var bool Whether this template lets content through via an xsl:apply-templates element
	*/
	protected $isPassthrough = false;

	/**
	* @var bool Whether all branches use the transparent content model
	*/
	protected $isTransparent = false;

	/**
	* @var bool Whether all branches have an ancestor that is a void element
	*/
	protected $isVoid = true;

	/**
	* @var array Names of every last HTML element that precedes an <xsl:apply-templates/> node
	*/
	protected $leafNodes = [];

	/**
	* @var bool Whether any branch has an element that preserves new lines by default (e.g. <pre>)
	*/
	protected $preservesNewLines = false;

	/**
	* @var array Bitfield of the first HTML element of every branch
	*/
	protected $rootBitfields = [];

	/**
	* @var array Names of every HTML element that have no HTML parent
	*/
	protected $rootNodes = [];

	/**
	* @var DOMXPath XPath engine associated with $this->dom
	*/
	protected $xpath;

	/**
	* Constructor
	*
	* @param  string $template Template content
	*/
	public function __construct($template)
	{
		$this->dom   = TemplateHelper::loadTemplate($template);
		$this->xpath = new DOMXPath($this->dom);

		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}

	/**
	* Return whether this template allows a given child
	*
	* @param  TemplateInspector $child
	* @return bool
	*/
	public function allowsChild(TemplateInspector $child)
	{
		// Sometimes, a template can technically be allowed as a child but denied as a descendant
		if (!$this->allowsDescendant($child))
		{
			return false;
		}

		foreach ($child->rootBitfields as $rootBitfield)
		{
			foreach ($this->allowChildBitfields as $allowChildBitfield)
			{
				if (!self::match($rootBitfield, $allowChildBitfield))
				{
					return false;
				}
			}
		}

		if (!$this->allowsText && $child->hasRootText)
		{
			return false;
		}

		return true;
	}

	/**
	* Return whether this template allows a given descendant
	*
	* @param  TemplateInspector $descendant
	* @return bool
	*/
	public function allowsDescendant(TemplateInspector $descendant)
	{
		// Test whether the descendant is explicitly disallowed
		if (self::match($descendant->contentBitfield, $this->denyDescendantBitfield))
		{
			return false;
		}

		// Test whether the descendant contains any elements and we disallow elements
		if (!$this->allowsChildElements && $descendant->hasElements)
		{
			return false;
		}

		return true;
	}

	/**
	* Return whether this template allows elements as children
	*
	* @return bool
	*/
	public function allowsChildElements()
	{
		return $this->allowsChildElements;
	}

	/**
	* Return whether this template allows text nodes as children
	*
	* @return bool
	*/
	public function allowsText()
	{
		return $this->allowsText;
	}

	/**
	* Return whether this template automatically closes given parent template
	*
	* @param  TemplateInspector $parent
	* @return bool
	*/
	public function closesParent(TemplateInspector $parent)
	{
		foreach ($this->rootNodes as $rootName)
		{
			if (empty(self::$htmlElements[$rootName]['cp']))
			{
				continue;
			}

			foreach ($parent->leafNodes as $leafName)
			{
				if (in_array($leafName, self::$htmlElements[$rootName]['cp'], true))
				{
					// If any of this template's root node closes one of the parent's leaf node, we
					// consider that this template closes the other one
					return true;
				}
			}
		}

		return false;
	}

	/**
	* Return the source template as a DOMDocument
	*
	* NOTE: the document should not be modified
	*
	* @return DOMDocument
	*/
	public function getDOM()
	{
		return $this->dom;
	}

	/**
	* Return whether this template should be considered a block-level element
	*
	* @return bool
	*/
	public function isBlock()
	{
		return $this->isBlock;
	}

	/**
	* Return whether this template adds to the list of active formatting elements
	*
	* @return bool
	*/
	public function isFormattingElement()
	{
		return $this->isFormattingElement;
	}

	/**
	* Return whether this template uses the "empty" content model
	*
	* @return bool
	*/
	public function isEmpty()
	{
		return $this->isEmpty;
	}

	/**
	* Return whether this template represents a single iframe
	*
	* @return bool
	*/
	public function isIframe()
	{
		return ($this->dom->getElementsByTagName('iframe')->length === 1);
	}

	/**
	* Return whether this template lets content through via an xsl:apply-templates element
	*
	* @return bool
	*/
	public function isPassthrough()
	{
		return $this->isPassthrough;
	}

	/**
	* Return whether this template uses the "transparent" content model
	*
	* @return bool
	*/
	public function isTransparent()
	{
		return $this->isTransparent;
	}

	/**
	* Return whether all branches have an ancestor that is a void element
	*
	* @return bool
	*/
	public function isVoid()
	{
		return $this->isVoid;
	}

	/**
	* Return whether this template preserves the whitespace in its descendants
	*
	* @return bool
	*/
	public function preservesNewLines()
	{
		return $this->preservesNewLines;
	}

	/**
	* Analyses the content of the whole template and set $this->contentBitfield accordingly
	*/
	protected function analyseContent()
	{
		// Get all non-XSL elements
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]';

		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= $this->getBitfield($node->localName, 'c', $node);
			$this->hasElements = true;
		}

		// Test whether this template is passthrough
		$this->isPassthrough = (bool) $this->xpath->evaluate('count(//xsl:apply-templates)');
	}

	/**
	* Records the HTML elements (and their bitfield) rendered at the root of the template
	*/
	protected function analyseRootNodes()
	{
		// Get every non-XSL element with no non-XSL ancestor. This should return us the first
		// HTML element of every branch
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]'
		       . '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';

		foreach ($this->xpath->query($query) as $node)
		{
			$elName = $node->localName;

			// Save the actual name of the root node
			$this->rootNodes[] = $elName;

			if (!isset(self::$htmlElements[$elName]))
			{
				// Unknown elements are treated as if they were a <span> element
				$elName = 'span';
			}

			// If any root node is a block-level element, we'll mark the template as such
			if ($this->elementIsBlock($elName, $node))
			{
				$this->isBlock = true;
			}

			$this->rootBitfields[] = $this->getBitfield($elName, 'c', $node);
		}

		// Test for non-whitespace text nodes at the root. For that we need a predicate that filters
		// out: nodes with a non-XSL ancestor,
		$predicate = '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';

		// ..and nodes with an <xsl:attribute/>, <xsl:comment/> or <xsl:variable/> ancestor
		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';

		$query = '//text()[normalize-space() != ""]' . $predicate
		       . '|'
		       . '//xsl:text[normalize-space() != ""]' . $predicate
		       . '|'
		       . '//xsl:value-of' . $predicate;

		if ($this->evaluate($query, $this->dom->documentElement))
		{
			$this->hasRootText = true;
		}
	}

	/**
	* Analyses each branch that leads to an <xsl:apply-templates/> tag
	*/
	protected function analyseBranches()
	{
		/**
		* @var array allowChild bitfield for each branch
		*/
		$branchBitfields = [];

		/**
		* @var bool Whether this template should be considered a formatting element
		*/
		$isFormattingElement = true;

		// Consider this template transparent unless we find out there are no branches or that one
		// of the branches is not transparent
		$this->isTransparent = true;

		// For each <xsl:apply-templates/> element...
		foreach ($this->getXSLElements('apply-templates') as $applyTemplates)
		{
			// ...we retrieve all non-XSL ancestors
			$nodes = $this->xpath->query(
				'ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]',
				$applyTemplates
			);

			/**
			* @var bool Whether this branch allows elements
			*/
			$allowsChildElements = true;

			/**
			* @var bool Whether this branch allows text nodes
			*/
			$allowsText = true;

			/**
			* @var string allowChild bitfield for current branch. Starts with the value associated
			*             with <div> in order to approximate a value if the whole branch uses the
			*             transparent content model
			*/
			$branchBitfield = self::$htmlElements['div']['ac'];

			/**
			* @var bool Whether this branch denies all non-text descendants
			*/
			$isEmpty = false;

			/**
			* @var bool Whether this branch contains a void element
			*/
			$isVoid = false;

			/**
			* @var string Name of the last node of this branch
			*/
			$leafNode = null;

			/**
			* @var boolean Whether this branch preserves new lines
			*/
			$preservesNewLines = false;

			foreach ($nodes as $node)
			{
				$elName = $leafNode = $node->localName;

				if (!isset(self::$htmlElements[$elName]))
				{
					// Unknown elements are treated as if they were a <span> element
					$elName = 'span';
				}

				// Test whether the element is void
				if ($this->hasProperty($elName, 'v', $node))
				{
					$isVoid = true;
				}

				// Test whether the element uses the "empty" content model
				if ($this->hasProperty($elName, 'e', $node))
				{
					$isEmpty = true;
				}

				if (!$this->hasProperty($elName, 't', $node))
				{
					// If the element isn't transparent, we reset its bitfield
					$branchBitfield = "\0";

					// Also, it means that the template itself isn't transparent
					$this->isTransparent = false;
				}

				// Test whether this element is a formatting element
				if (!$this->hasProperty($elName, 'fe', $node)
				 && !$this->isFormattingSpan($node))
				{
					$isFormattingElement = false;
				}

				// Test whether this branch allows elements
				$allowsChildElements = !$this->hasProperty($elName, 'to', $node);

				// Test whether this branch allows text nodes
				$allowsText = !$this->hasProperty($elName, 'nt', $node);

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= $this->getBitfield($elName, 'ac', $node);

				// denyDescendant rules are cumulative
				$this->denyDescendantBitfield |= $this->getBitfield($elName, 'dd', $node);

				// Test whether this branch preserves whitespace by inspecting the current element
				// and the value of its style attribute. Technically, this block of code also tests
				// this element's descendants' style attributes but the result is the same as we
				// need to check every element of this branch in order
				$style = '';

				if ($this->hasProperty($elName, 'pre', $node))
				{
					$style .= 'white-space:pre;';
				}

				if ($node->hasAttribute('style'))
				{
					$style .= $node->getAttribute('style') . ';';
				}

				$attributes = $this->xpath->query('.//xsl:attribute[@name="style"]', $node);
				foreach ($attributes as $attribute)
				{
					$style .= $attribute->textContent;
				}

				preg_match_all(
					'/white-space\\s*:\\s*(no|pre)/i',
					strtolower($style),
					$matches
				);
				foreach ($matches[1] as $match)
				{
					// TRUE:  "pre", "pre-line" and "pre-wrap"
					// FALSE: "normal", "nowrap"
					$preservesNewLines = ($match === 'pre');
				}
			}

			// Add this branch's bitfield to the list
			$branchBitfields[] = $branchBitfield;

			// Save the name of the last node processed
			if (isset($leafNode))
			{
				$this->leafNodes[] = $leafNode;
			}

			// If any branch disallows elements, the template disallows elements
			if (!$allowsChildElements)
			{
				$this->allowsChildElements = false;
			}

			// If any branch disallows text, the template disallows text
			if (!$allowsText)
			{
				$this->allowsText = false;
			}

			// If any branch is not empty, the template is not empty
			if (!$isEmpty)
			{
				$this->isEmpty = false;
			}

			// If any branch is not void, the template is not void
			if (!$isVoid)
			{
				$this->isVoid = false;
			}

			// If any branch preserves new lines, the template preserves new lines
			if ($preservesNewLines)
			{
				$this->preservesNewLines = true;
			}
		}

		if (empty($branchBitfields))
		{
			// No branches => not transparent and no child elements
			$this->allowChildBitfields = ["\0"];
			$this->allowsChildElements = false;
			$this->isTransparent       = false;
		}
		else
		{
			$this->allowChildBitfields = $branchBitfields;

			// Set the isFormattingElement property to our final value, but only if this template
			// had any branches
			if (!empty($this->leafNodes))
			{
				$this->isFormattingElement = $isFormattingElement;
			}
		}
	}

	/**
	* Test whether given element is a block-level element
	*
	* @param  string     $elName Element name
	* @param  DOMElement $node   Context node
	* @return bool
	*/
	protected function elementIsBlock($elName, DOMElement $node)
	{
		$style = $this->getStyle($node);
		if (preg_match('(\\bdisplay\\s*:\\s*block)i', $style))
		{
			return true;
		}
		if (preg_match('(\\bdisplay\\s*:\\s*(?:inli|no)ne)i', $style))
		{
			return false;
		}

		return $this->hasProperty($elName, 'b', $node);
	}

	/**
	* Evaluate a boolean XPath query
	*
	* @param  string     $query XPath query
	* @param  DOMElement $node  Context node
	* @return boolean
	*/
	protected function evaluate($query, DOMElement $node)
	{
		return $this->xpath->evaluate('boolean(' . $query . ')', $node);
	}

	/**
	* Retrieve and return the inline style assigned to given element
	*
	* @param  DOMElement $node Context node
	* @return string
	*/
	protected function getStyle(DOMElement $node)
	{
		// Start with the inline attribute
		$style = $node->getAttribute('style');

		// Add the content of any xsl:attribute named "style". This will miss optional attributes
		$xpath = new DOMXPath($node->ownerDocument);
		$query = 'xsl:attribute[@name="style"]';
		foreach ($xpath->query($query, $node) as $attribute)
		{
			$style .= ';' . $attribute->textContent;
		}

		return $style;
	}

	/**
	* Get all XSL elements of given name
	*
	* @param  string      $elName XSL element's name, e.g. "apply-templates"
	* @return \DOMNodeList
	*/
	protected function getXSLElements($elName)
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', $elName);
	}

	/**
	* Test whether given node is a span element used for formatting
	*
	* Will return TRUE if the node is a span element with a class attribute and/or a style attribute
	* and no other attributes
	*
	* @param  DOMElement $node
	* @return boolean
	*/
	protected function isFormattingSpan(DOMElement $node)
	{
		if ($node->nodeName !== 'span')
		{
			return false;
		}

		if ($node->getAttribute('class') === '' && $node->getAttribute('style') === '')
		{
			return false;
		}

		foreach ($node->attributes as $attrName => $attribute)
		{
			if ($attrName !== 'class' && $attrName !== 'style')
			{
				return false;
			}
		}

		return true;
	}

	/**
	* "What is this?" you might ask. This is basically a compressed version of the HTML5 content
	* models and rules, with some liberties taken.
	*
	* For each element, up to three bitfields are defined: "c", "ac" and "dd". Bitfields are stored
	* as raw bytes, formatted using the octal notation to keep the sources ASCII.
	*
	*   "c" represents the categories the element belongs to. The categories are comprised of HTML5
	*   content models (such as "phrasing content" or "interactive content") plus a few special
	*   categories created to cover the parts of the specs that refer to "a group of X and Y
	*   elements" rather than a specific content model.
	*
	*   "ac" represents the categories that are allowed as children of given element.
	*
	*   "dd" represents the categories that must not appear as a descendant of given element.
	*
	* Sometimes, HTML5 specifies some restrictions on when an element can accept certain children,
	* or what categories the element belongs to. For example, an <img> element is only part of the
	* "interactive content" category if it has a "usemap" attribute. Those restrictions are
	* expressed as an XPath expression and stored using the concatenation of the key of the bitfield
	* plus the bit number of the category. For instance, if "interactive content" got assigned to
	* bit 2, the definition of the <img> element will contain a key "c2" with value "@usemap".
	*
	* Additionally, other flags are set:
	*
	*   "t" indicates that the element uses the "transparent" content model.
	*   "e" indicates that the element uses the "empty" content model.
	*   "v" indicates that the element is a void element.
	*   "nt" indicates that the element does not accept text nodes. (no text)
	*   "to" indicates that the element should only contain text. (text-only)
	*   "fe" indicates that the element is a formatting element. It will automatically be reopened
	*   when closed by an end tag of a different name.
	*   "b" indicates that the element is not phrasing content, which makes it likely to act like
	*   a block element.
	*
	* Finally, HTML5 defines "optional end tag" rules, where one element automatically closes its
	* predecessor. Those are used to generate closeParent rules and are stored in the "cp" key.
	*
	* @var array
	* @see /scripts/patchTemplateInspector.php
	*/
	protected static $htmlElements = [
		'a'=>['c'=>"\17\0\0\0\0\1",'c3'=>'@href','ac'=>"\0",'dd'=>"\10\0\0\0\0\1",'t'=>1,'fe'=>1],
		'abbr'=>['c'=>"\7",'ac'=>"\4"],
		'address'=>['c'=>"\3\40",'ac'=>"\1",'dd'=>"\0\45",'b'=>1,'cp'=>['p']],
		'article'=>['c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'aside'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'audio'=>['c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\0\104",'ac26'=>'not(@src)','dd'=>"\0\0\0\0\0\2",'dd41'=>'@src','t'=>1],
		'b'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'base'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'bdi'=>['c'=>"\7",'ac'=>"\4"],
		'bdo'=>['c'=>"\7",'ac'=>"\4"],
		'blockquote'=>['c'=>"\203",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'body'=>['c'=>"\200\0\4",'ac'=>"\1",'b'=>1],
		'br'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1],
		'button'=>['c'=>"\117",'ac'=>"\4",'dd'=>"\10"],
		'canvas'=>['c'=>"\47",'ac'=>"\0",'t'=>1],
		'caption'=>['c'=>"\0\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1],
		'cite'=>['c'=>"\7",'ac'=>"\4"],
		'code'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'col'=>['c'=>"\0\0\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'colgroup'=>['c'=>"\0\2",'ac'=>"\0\0\20",'ac20'=>'not(@span)','nt'=>1,'e'=>1,'e0'=>'@span','b'=>1],
		'data'=>['c'=>"\7",'ac'=>"\4"],
		'datalist'=>['c'=>"\5",'ac'=>"\4\200\0\10"],
		'dd'=>['c'=>"\0\0\200",'ac'=>"\1",'b'=>1,'cp'=>['dd','dt']],
		'del'=>['c'=>"\5",'ac'=>"\0",'t'=>1],
		'details'=>['c'=>"\213",'ac'=>"\1\0\0\2",'b'=>1,'cp'=>['p']],
		'dfn'=>['c'=>"\7\0\0\0\40",'ac'=>"\4",'dd'=>"\0\0\0\0\40"],
		'div'=>['c'=>"\3",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'dl'=>['c'=>"\3",'c1'=>'dt and dd','ac'=>"\0\200\200",'nt'=>1,'b'=>1,'cp'=>['p']],
		'dt'=>['c'=>"\0\0\200",'ac'=>"\1",'dd'=>"\0\5\0\40",'b'=>1,'cp'=>['dd','dt']],
		'em'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'embed'=>['c'=>"\57",'nt'=>1,'e'=>1,'v'=>1],
		'fieldset'=>['c'=>"\303",'ac'=>"\1\0\0\20",'b'=>1,'cp'=>['p']],
		'figcaption'=>['c'=>"\0\0\0\0\0\4",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'figure'=>['c'=>"\203",'ac'=>"\1\0\0\0\0\4",'b'=>1,'cp'=>['p']],
		'footer'=>['c'=>"\3\40",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'form'=>['c'=>"\3\0\0\0\20",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['p']],
		'h1'=>['c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h2'=>['c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h3'=>['c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h4'=>['c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h5'=>['c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h6'=>['c'=>"\3\1",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'head'=>['c'=>"\0\0\4",'ac'=>"\20",'nt'=>1,'b'=>1],
		'header'=>['c'=>"\3\40\0\40",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'hr'=>['c'=>"\1\100",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>['p']],
		'html'=>['c'=>"\0",'ac'=>"\0\0\4",'nt'=>1,'b'=>1],
		'i'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'iframe'=>['c'=>"\57",'ac'=>"\4"],
		'img'=>['c'=>"\57\20\10",'c3'=>'@usemap','nt'=>1,'e'=>1,'v'=>1],
		'input'=>['c'=>"\17\20",'c3'=>'@type!="hidden"','c12'=>'@type!="hidden" or @type="hidden"','c1'=>'@type!="hidden"','nt'=>1,'e'=>1,'v'=>1],
		'ins'=>['c'=>"\7",'ac'=>"\0",'t'=>1],
		'kbd'=>['c'=>"\7",'ac'=>"\4"],
		'keygen'=>['c'=>"\117",'nt'=>1,'e'=>1,'v'=>1],
		'label'=>['c'=>"\17\20\0\0\4",'ac'=>"\4",'dd'=>"\0\0\1\0\4"],
		'legend'=>['c'=>"\0\0\0\20",'ac'=>"\4",'b'=>1],
		'li'=>['c'=>"\0\0\0\0\200",'ac'=>"\1",'b'=>1,'cp'=>['li']],
		'link'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'main'=>['c'=>"\3\0\0\0\10",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'mark'=>['c'=>"\7",'ac'=>"\4"],
		'media element'=>['c'=>"\0\0\0\0\0\2",'nt'=>1,'b'=>1],
		'menu'=>['c'=>"\1\100",'ac'=>"\0\300",'nt'=>1,'b'=>1,'cp'=>['p']],
		'menuitem'=>['c'=>"\0\100",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meta'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meter'=>['c'=>"\7\0\1\0\2",'ac'=>"\4",'dd'=>"\0\0\0\0\2"],
		'nav'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'noscript'=>['c'=>"\25",'nt'=>1],
		'object'=>['c'=>"\147",'ac'=>"\0\0\0\0\1",'t'=>1],
		'ol'=>['c'=>"\3",'c1'=>'li','ac'=>"\0\200\0\0\200",'nt'=>1,'b'=>1,'cp'=>['p']],
		'optgroup'=>['c'=>"\0\0\2",'ac'=>"\0\200\0\10",'nt'=>1,'b'=>1,'cp'=>['optgroup','option']],
		'option'=>['c'=>"\0\0\2\10",'b'=>1,'cp'=>['option']],
		'output'=>['c'=>"\107",'ac'=>"\4"],
		'p'=>['c'=>"\3",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'param'=>['c'=>"\0\0\0\0\1",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'picture'=>['c'=>"\45",'ac'=>"\0\200\10",'nt'=>1],
		'pre'=>['c'=>"\3",'ac'=>"\4",'pre'=>1,'b'=>1,'cp'=>['p']],
		'progress'=>['c'=>"\7\0\1\1",'ac'=>"\4",'dd'=>"\0\0\0\1"],
		'q'=>['c'=>"\7",'ac'=>"\4"],
		'rb'=>['c'=>"\0\10",'ac'=>"\4",'b'=>1],
		'rp'=>['c'=>"\0\10\100",'ac'=>"\4",'b'=>1,'cp'=>['rp','rt']],
		'rt'=>['c'=>"\0\10\100",'ac'=>"\4",'b'=>1,'cp'=>['rp','rt']],
		'rtc'=>['c'=>"\0\10",'ac'=>"\4\0\100",'b'=>1],
		'ruby'=>['c'=>"\7",'ac'=>"\4\10"],
		's'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'samp'=>['c'=>"\7",'ac'=>"\4"],
		'script'=>['c'=>"\25\200",'to'=>1],
		'section'=>['c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'select'=>['c'=>"\117",'ac'=>"\0\200\2",'nt'=>1],
		'small'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'source'=>['c'=>"\0\0\10\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'span'=>['c'=>"\7",'ac'=>"\4"],
		'strong'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'style'=>['c'=>"\20",'to'=>1,'b'=>1],
		'sub'=>['c'=>"\7",'ac'=>"\4"],
		'summary'=>['c'=>"\0\0\0\2",'ac'=>"\4\1",'b'=>1],
		'sup'=>['c'=>"\7",'ac'=>"\4"],
		'table'=>['c'=>"\3\0\0\200",'ac'=>"\0\202",'nt'=>1,'b'=>1,'cp'=>['p']],
		'tbody'=>['c'=>"\0\2",'ac'=>"\0\200\0\0\100",'nt'=>1,'b'=>1,'cp'=>['tbody','td','tfoot','th','thead','tr']],
		'td'=>['c'=>"\200\0\40",'ac'=>"\1",'b'=>1,'cp'=>['td','th']],
		'template'=>['c'=>"\25\200\20",'nt'=>1],
		'textarea'=>['c'=>"\117",'pre'=>1,'to'=>1],
		'tfoot'=>['c'=>"\0\2",'ac'=>"\0\200\0\0\100",'nt'=>1,'b'=>1,'cp'=>['tbody','td','th','thead','tr']],
		'th'=>['c'=>"\0\0\40",'ac'=>"\1",'dd'=>"\0\5\0\40",'b'=>1,'cp'=>['td','th']],
		'thead'=>['c'=>"\0\2",'ac'=>"\0\200\0\0\100",'nt'=>1,'b'=>1],
		'time'=>['c'=>"\7",'ac'=>"\4",'ac2'=>'@datetime'],
		'title'=>['c'=>"\20",'to'=>1,'b'=>1],
		'tr'=>['c'=>"\0\2\0\0\100",'ac'=>"\0\200\40",'nt'=>1,'b'=>1,'cp'=>['td','th','tr']],
		'track'=>['c'=>"\0\0\0\100",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'u'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'ul'=>['c'=>"\3",'c1'=>'li','ac'=>"\0\200\0\0\200",'nt'=>1,'b'=>1,'cp'=>['p']],
		'var'=>['c'=>"\7",'ac'=>"\4"],
		'video'=>['c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\0\104",'ac26'=>'not(@src)','dd'=>"\0\0\0\0\0\2",'dd41'=>'@src','t'=>1],
		'wbr'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1]
	];

	/**
	* Get the bitfield value for a given element name in a given context
	*
	* @param  string     $elName Name of the HTML element
	* @param  string     $k      Bitfield name: either 'c', 'ac' or 'dd'
	* @param  DOMElement $node   Context node (not necessarily the same as $elName)
	* @return string
	*/
	protected function getBitfield($elName, $k, DOMElement $node)
	{
		if (!isset(self::$htmlElements[$elName][$k]))
		{
			return "\0";
		}

		$bitfield = self::$htmlElements[$elName][$k];
		foreach (str_split($bitfield, 1) as $byteNumber => $char)
		{
			$byteValue = ord($char);
			for ($bitNumber = 0; $bitNumber < 8; ++$bitNumber)
			{
				$bitValue = 1 << $bitNumber;
				if (!($byteValue & $bitValue))
				{
					// The bit is not set
					continue;
				}

				$n = $byteNumber * 8 + $bitNumber;

				// Test for an XPath condition for that category
				if (isset(self::$htmlElements[$elName][$k . $n]))
				{
					$xpath = 'boolean(' . self::$htmlElements[$elName][$k . $n] . ')';

					// If the XPath condition is not fulfilled...
					if (!$this->evaluate($xpath, $node))
					{
						// ...turn off the corresponding bit
						$byteValue ^= $bitValue;

						// Update the original bitfield
						$bitfield[$byteNumber] = chr($byteValue);
					}
				}
			}
		}

		return $bitfield;
	}

	/**
	* Test whether given element has given property in context
	*
	* @param  string     $elName   Element name
	* @param  string     $propName Property name, see self::$htmlElements
	* @param  DOMElement $node     Context node
	* @return bool
	*/
	protected function hasProperty($elName, $propName, DOMElement $node)
	{
		if (!empty(self::$htmlElements[$elName][$propName]))
		{
			// Test the XPath condition
			if (!isset(self::$htmlElements[$elName][$propName . '0'])
			 || $this->evaluate(self::$htmlElements[$elName][$propName . '0'], $node))
			{
				return true;
			}
		}

		return false;
	}

	/**
	* Test whether two bitfields have any bits in common
	*
	* @param  string $bitfield1
	* @param  string $bitfield2
	* @return bool
	*/
	protected static function match($bitfield1, $bitfield2)
	{
		return (trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}