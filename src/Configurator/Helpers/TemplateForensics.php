<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;

/*
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
* @see  /scripts/patchTemplateForensics.php
*/
class TemplateForensics
{
	/*
	* @var string allowChild bitfield (all branches)
	*/
	protected $allowChildBitfield = "\0";

	/*
	* @var bool Whether elements are allowed as children
	*/
	protected $allowsChildElements = \true;

	/*
	* @var bool Whether text nodes are allowed as children
	*/
	protected $allowsText = \true;

	/*
	* @var string OR-ed bitfield representing all of the categories used by this template
	*/
	protected $contentBitfield = "\0";

	/*
	* @var string denyDescendant bitfield
	*/
	protected $denyDescendantBitfield = "\0";

	/*
	* @var DOMDocument Document containing the template
	*/
	protected $dom;

	/*
	* @var bool Whether this template contains any HTML elements
	*/
	protected $hasElements = \false;

	/*
	* @var bool Whether this template renders non-whitespace text nodes at its root
	*/
	protected $hasRootText = \false;

	/*
	* @var bool Whether this template should be considered a block-level element
	*/
	protected $isBlock = \false;

	/*
	* @var bool Whether the template uses the "empty" content model
	*/
	protected $isEmpty = \true;

	/*
	* @var bool Whether this template adds to the list of active formatting elements
	*/
	protected $isFormattingElement = \false;

	/*
	* @var bool Whether this template lets content through via an xsl:apply-templates element
	*/
	protected $isPassthrough = \false;

	/*
	* @var bool Whether all branches use the transparent content model
	*/
	protected $isTransparent = \false;

	/*
	* @var bool Whether all branches have an ancestor that is a void element
	*/
	protected $isVoid = \true;

	/*
	* @var array Names of every last HTML element that precedes an <xsl:apply-templates/> node
	*/
	protected $leafNodes = array();

	/*
	* @var bool Whether any branch has an element that preserves new lines by default (e.g. <pre>)
	*/
	protected $preservesNewLines = \false;

	/*
	* @var array Bitfield of the first HTML element of every branch
	*/
	protected $rootBitfields = array();

	/*
	* @var array Names of every HTML element that have no HTML parent
	*/
	protected $rootNodes = array();

	/*
	* @var DOMXPath XPath engine associated with $this->dom
	*/
	protected $xpath;

	/*
	* Constructor
	*
	* @param  string $template Template content
	* @return void
	*/
	public function __construct($template)
	{
		$this->dom   = TemplateHelper::loadTemplate($template);
		$this->xpath = new DOMXPath($this->dom);

		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}

	/*
	* Return whether this template allows a given child
	*
	* @param  self $child
	* @return bool
	*/
	public function allowsChild(self $child)
	{
		// Sometimes, a template can technically be allowed as a child but denied as a descendant
		if (!$this->allowsDescendant($child))
			return \false;

		foreach ($child->rootBitfields as $rootBitfield)
			if (!self::match($rootBitfield, $this->allowChildBitfield))
				return \false;

		if (!$this->allowsText && $child->hasRootText)
			return \false;

		return \true;
	}

	/*
	* Return whether this template allows a given descendant
	*
	* @param  self $descendant
	* @return bool
	*/
	public function allowsDescendant(self $descendant)
	{
		// Test whether the descendant is explicitly disallowed
		if (self::match($descendant->contentBitfield, $this->denyDescendantBitfield))
			return \false;

		// Test whether the descendant contains any elements and we disallow elements
		if (!$this->allowsChildElements && $descendant->hasElements)
			return \false;

		return \true;
	}

	/*
	* Return whether this template allows elements as children
	*
	* @return bool
	*/
	public function allowsChildElements()
	{
		return $this->allowsChildElements;
	}

	/*
	* Return whether this template allows text nodes as children
	*
	* @return bool
	*/
	public function allowsText()
	{
		return $this->allowsText;
	}

	/*
	* Return whether this template automatically closes given parent template
	*
	* @param  self $parent
	* @return bool
	*/
	public function closesParent(self $parent)
	{
		foreach ($this->rootNodes as $rootName)
		{
			if (empty(self::$htmlElements[$rootName]['cp']))
				continue;

			foreach ($parent->leafNodes as $leafName)
				if (\in_array($leafName, self::$htmlElements[$rootName]['cp'], \true))
					// If any of this template's root node closes one of the parent's leaf node, we
					// consider that this template closes the other one
					return \true;
		}

		return \false;
	}

	/*
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

	/*
	* Return whether this template should be considered a block-level element
	*
	* @return bool
	*/
	public function isBlock()
	{
		return $this->isBlock;
	}

	/*
	* Return whether this template adds to the list of active formatting elements
	*
	* @return bool
	*/
	public function isFormattingElement()
	{
		return $this->isFormattingElement;
	}

	/*
	* Return whether this template uses the "empty" content model
	*
	* @return bool
	*/
	public function isEmpty()
	{
		return $this->isEmpty;
	}

	/*
	* Return whether this template lets content through via an xsl:apply-templates element
	*
	* @return bool
	*/
	public function isPassthrough()
	{
		return $this->isPassthrough;
	}

	/*
	* Return whether this template uses the "transparent" content model
	*
	* @return bool
	*/
	public function isTransparent()
	{
		return $this->isTransparent;
	}

	/*
	* Return whether all branches have an ancestor that is a void element
	*
	* @return bool
	*/
	public function isVoid()
	{
		return $this->isVoid;
	}

	/*
	* Return whether this template preserves the whitespace in its descendants
	*
	* @return bool
	*/
	public function preservesNewLines()
	{
		return $this->preservesNewLines;
	}

	/*
	* Analyses the content of the whole template and set $this->contentBitfield accordingly
	*/
	protected function analyseContent()
	{
		// Get all non-XSL elements
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]';

		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= $this->getBitfield($node->localName, 'c', $node);
			$this->hasElements = \true;
		}

		// Test whether this template is passthrough
		$this->isPassthrough = (bool) $this->xpath->evaluate('count(//xsl:apply-templates)');
	}

	/*
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
				// Unknown elements are treated as if they were a <span> element
				$elName = 'span';

			// If any root node is a block-level element, we'll mark the template as such
			if ($this->hasProperty($elName, 'b', $node))
				$this->isBlock = \true;

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
			$this->hasRootText = \true;
	}

	/*
	* Analyses each branch that leads to an <xsl:apply-templates/> tag
	*/
	protected function analyseBranches()
	{
		/*
		* @var array allowChild bitfield for each branch
		*/
		$branchBitfields = array();

		/*
		* @var bool Whether this template should be considered a formatting element
		*/
		$isFormattingElement = \true;

		// Consider this template transparent unless we find out there are no branches or that one
		// of the branches is not transparent
		$this->isTransparent = \true;

		// For each <xsl:apply-templates/> element...
		foreach ($this->getXSLElements('apply-templates') as $applyTemplates)
		{
			// ...we retrieve all non-XSL ancestors
			$nodes = $this->xpath->query(
				'ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]',
				$applyTemplates
			);

			/*
			* @var bool Whether this branch allows elements
			*/
			$allowsChildElements = \true;

			/*
			* @var bool Whether this branch allows text nodes
			*/
			$allowsText = \true;

			/*
			* @var string allowChild bitfield for current branch. Starts with the value associated
			*             with <div> in order to approximate a value if the whole branch uses the
			*             transparent content model
			*/
			$branchBitfield = self::$htmlElements['div']['ac'];

			/*
			* @var bool Whether this branch denies all non-text descendants
			*/
			$isEmpty = \false;

			/*
			* @var bool Whether this branch contains a void element
			*/
			$isVoid = \false;

			/*
			* @var string Name of the last node of this branch
			*/
			$leafNode = \null;

			/*
			* @var boolean Whether this branch preserves new lines
			*/
			$preservesNewLines = \false;

			foreach ($nodes as $node)
			{
				$elName = $leafNode = $node->localName;

				if (!isset(self::$htmlElements[$elName]))
					// Unknown elements are treated as if they were a <span> element
					$elName = 'span';

				// Test whether the element is void
				if ($this->hasProperty($elName, 'v', $node))
					$isVoid = \true;

				// Test whether the element uses the "empty" content model
				if ($this->hasProperty($elName, 'e', $node))
					$isEmpty = \true;

				if (!$this->hasProperty($elName, 't', $node))
				{
					// If the element isn't transparent, we reset its bitfield
					$branchBitfield = "\0";

					// Also, it means that the template itself isn't transparent
					$this->isTransparent = \false;
				}

				// Test whether this element is a formatting element
				if (!$this->hasProperty($elName, 'fe', $node)
				 && !$this->isFormattingSpan($node))
					$isFormattingElement = \false;

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
					$style .= 'white-space:pre;';

				if ($node->hasAttribute('style'))
					$style .= $node->getAttribute('style') . ';';

				$attributes = $this->xpath->query('.//xsl:attribute[@name="style"]', $node);
				foreach ($attributes as $attribute)
					$style .= $attribute->textContent;

				\preg_match_all(
					'/white-space\\s*:\\s*(no|pre)/i',
					\strtolower($style),
					$matches
				);
				foreach ($matches[1] as $match)
					// TRUE:  "pre", "pre-line" and "pre-wrap"
					// FALSE: "normal", "nowrap"
					$preservesNewLines = ($match === 'pre');
			}

			// Add this branch's bitfield to the list
			$branchBitfields[] = $branchBitfield;

			// Save the name of the last node processed
			if (isset($leafNode))
				$this->leafNodes[] = $leafNode;

			// If any branch disallows elements, the template disallows elements
			if (!$allowsChildElements)
				$this->allowsChildElements = \false;

			// If any branch disallows text, the template disallows text
			if (!$allowsText)
				$this->allowsText = \false;

			// If any branch is not empty, the template is not empty
			if (!$isEmpty)
				$this->isEmpty = \false;

			// If any branch is not void, the template is not void
			if (!$isVoid)
				$this->isVoid = \false;

			// If any branch preserves new lines, the template preserves new lines
			if ($preservesNewLines)
				$this->preservesNewLines = \true;
		}

		if (empty($branchBitfields))
			// No branches => not transparent
			$this->isTransparent = \false;
		else
		{
			// Take the bitfield of each branch and reduce them to a single ANDed bitfield
			$this->allowChildBitfield = $branchBitfields[0];

			foreach ($branchBitfields as $branchBitfield)
				$this->allowChildBitfield &= $branchBitfield;

			// Set the isFormattingElement property to our final value, but only if this template
			// had any branches
			if (!empty($this->leafNodes))
				$this->isFormattingElement = $isFormattingElement;
		}
	}

	/*
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

	/*
	* Get all XSL elements of given name
	*
	* @param  string      $elName XSL element's name, e.g. "apply-templates"
	* @return \DOMNodeList
	*/
	protected function getXSLElements($elName)
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', $elName);
	}

	/*
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
			return \false;

		if ($node->getAttribute('class') === ''
		 && $node->getAttribute('style') === '')
			return \false;

		foreach ($node->attributes as $attrName => $attribute)
			if ($attrName !== 'class' && $attrName !== 'style')
				return \false;

		return \true;
	}

	/*
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
	*
	*   "e" indicates that the element uses the "empty" content model.
	*
	*   "v" indicates that the element is a void element.
	*
	*   "nt" indicates that the element does not accept text nodes. (no text)
	*
	*   "to" indicates that the element should only contain text. (text-only)
	*
	*   "fe" indicates that the element is a formatting element. It will automatically be reopened
	*   when closed by an end tag of a different name.
	*
	*   "b" indicates that the element is not phrasing content, which makes it likely to act like
	*   a block element.
	*
	* Finally, HTML5 defines "optional end tag" rules, where one element automatically closes its
	* predecessor. Those are used to generate closeParent rules and are stored in the "cp" key.
	*
	* @var array
	* @see /scripts/patchTemplateForensics.php
	*/
	protected static $htmlElements = array(
		'a'=>array('c'=>"\17",'ac'=>"\0",'dd'=>"\10",'t'=>1,'fe'=>1),
		'abbr'=>array('c'=>"\7",'ac'=>"\4"),
		'address'=>array('c'=>"\3\10",'ac'=>"\1",'dd'=>"\100\12",'b'=>1,'cp'=>array('p')),
		'area'=>array('c'=>"\5",'nt'=>1,'e'=>1,'v'=>1),
		'article'=>array('c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'aside'=>array('c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>array('p')),
		'audio'=>array('c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1),
		'b'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'base'=>array('c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'bdi'=>array('c'=>"\7",'ac'=>"\4"),
		'bdo'=>array('c'=>"\7",'ac'=>"\4"),
		'blockquote'=>array('c'=>"\3\1",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'body'=>array('c'=>"\0\1\2",'ac'=>"\1",'b'=>1),
		'br'=>array('c'=>"\5",'nt'=>1,'e'=>1,'v'=>1),
		'button'=>array('c'=>"\17",'ac'=>"\4",'dd'=>"\10"),
		'canvas'=>array('c'=>"\47",'ac'=>"\0",'t'=>1),
		'caption'=>array('c'=>"\200",'ac'=>"\1",'dd'=>"\0\0\0\10",'b'=>1),
		'cite'=>array('c'=>"\7",'ac'=>"\4"),
		'code'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'col'=>array('c'=>"\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'colgroup'=>array('c'=>"\200",'ac'=>"\0\0\4",'ac18'=>'not(@span)','nt'=>1,'e'=>1,'e0'=>'@span','b'=>1),
		'data'=>array('c'=>"\7",'ac'=>"\4"),
		'datalist'=>array('c'=>"\5",'ac'=>"\4\0\0\1"),
		'dd'=>array('c'=>"\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>array('dd','dt')),
		'del'=>array('c'=>"\5",'ac'=>"\0",'t'=>1),
		'dfn'=>array('c'=>"\7\0\0\0\2",'ac'=>"\4",'dd'=>"\0\0\0\0\2"),
		'div'=>array('c'=>"\3",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'dl'=>array('c'=>"\3",'ac'=>"\0\40\20",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'dt'=>array('c'=>"\0\0\20",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>array('dd','dt')),
		'em'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'embed'=>array('c'=>"\57",'nt'=>1,'e'=>1,'v'=>1),
		'fieldset'=>array('c'=>"\3\1",'ac'=>"\1\0\0\2",'b'=>1,'cp'=>array('p')),
		'figcaption'=>array('c'=>"\0\0\0\0\40",'ac'=>"\1",'b'=>1),
		'figure'=>array('c'=>"\3\1",'ac'=>"\1\0\0\0\40",'b'=>1),
		'footer'=>array('c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>array('p')),
		'form'=>array('c'=>"\3\0\0\0\1",'ac'=>"\1",'dd'=>"\0\0\0\0\1",'b'=>1,'cp'=>array('p')),
		'h1'=>array('c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h2'=>array('c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h3'=>array('c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h4'=>array('c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h5'=>array('c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h6'=>array('c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'head'=>array('c'=>"\0\0\2",'ac'=>"\20",'nt'=>1,'b'=>1),
		'header'=>array('c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>array('p')),
		'hr'=>array('c'=>"\1",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>array('p')),
		'html'=>array('c'=>"\0",'ac'=>"\0\0\2",'nt'=>1,'b'=>1),
		'i'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'iframe'=>array('c'=>"\57",'nt'=>1,'e'=>1,'to'=>1),
		'img'=>array('c'=>"\57",'c3'=>'@usemap','nt'=>1,'e'=>1,'v'=>1),
		'input'=>array('c'=>"\17",'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"','nt'=>1,'e'=>1,'v'=>1),
		'ins'=>array('c'=>"\7",'ac'=>"\0",'t'=>1),
		'kbd'=>array('c'=>"\7",'ac'=>"\4"),
		'keygen'=>array('c'=>"\17",'nt'=>1,'e'=>1,'v'=>1),
		'label'=>array('c'=>"\17\0\0\100",'ac'=>"\4",'dd'=>"\0\0\0\100"),
		'legend'=>array('c'=>"\0\0\0\2",'ac'=>"\4",'b'=>1),
		'li'=>array('c'=>"\0\0\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>array('li')),
		'link'=>array('c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'main'=>array('c'=>"\3\20\0\200",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'map'=>array('c'=>"\7",'ac'=>"\0",'t'=>1),
		'mark'=>array('c'=>"\7",'ac'=>"\4"),
		'meta'=>array('c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'meter'=>array('c'=>"\7\100\0\40",'ac'=>"\4",'dd'=>"\0\0\0\40"),
		'nav'=>array('c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>array('p')),
		'noscript'=>array('c'=>"\25\0\100",'ac'=>"\0",'dd'=>"\0\0\100",'t'=>1),
		'object'=>array('c'=>"\57",'c3'=>'@usemap','ac'=>"\0\0\0\20",'t'=>1),
		'ol'=>array('c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'optgroup'=>array('c'=>"\0\200",'ac'=>"\0\40\0\1",'nt'=>1,'b'=>1,'cp'=>array('optgroup','option')),
		'option'=>array('c'=>"\0\200\0\1",'e'=>1,'e0'=>'@label and @value','to'=>1,'b'=>1,'cp'=>array('option')),
		'output'=>array('c'=>"\7",'ac'=>"\4"),
		'p'=>array('c'=>"\3",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'param'=>array('c'=>"\0\0\0\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'pre'=>array('c'=>"\3",'ac'=>"\4",'pre'=>1,'b'=>1,'cp'=>array('p')),
		'progress'=>array('c'=>"\7\100\40",'ac'=>"\4",'dd'=>"\0\0\40"),
		'q'=>array('c'=>"\7",'ac'=>"\4"),
		'rb'=>array('c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>array('rb','rp','rt','rtc')),
		'rp'=>array('c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>array('rb','rp','rtc')),
		'rt'=>array('c'=>"\0\4\0\0\10",'ac'=>"\4",'b'=>1,'cp'=>array('rb','rp','rt')),
		'rtc'=>array('c'=>"\0\4",'ac'=>"\4\0\0\0\10",'b'=>1,'cp'=>array('rb','rp','rt','rtc')),
		'ruby'=>array('c'=>"\7",'ac'=>"\4\4"),
		's'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'samp'=>array('c'=>"\7",'ac'=>"\4"),
		'script'=>array('c'=>"\25\40",'e'=>1,'e0'=>'@src','to'=>1),
		'section'=>array('c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'select'=>array('c'=>"\17",'ac'=>"\0\240",'nt'=>1),
		'small'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'source'=>array('c'=>"\0\0\200",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'span'=>array('c'=>"\7",'ac'=>"\4"),
		'strong'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'style'=>array('c'=>"\20",'to'=>1,'b'=>1),
		'sub'=>array('c'=>"\7",'ac'=>"\4"),
		'sup'=>array('c'=>"\7",'ac'=>"\4"),
		'table'=>array('c'=>"\3\0\0\10",'ac'=>"\200\40",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'tbody'=>array('c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>array('tbody','tfoot','thead')),
		'td'=>array('c'=>"\0\1\10",'ac'=>"\1",'b'=>1,'cp'=>array('td','th')),
		'template'=>array('c'=>"\25\40\4",'ac'=>"\21"),
		'textarea'=>array('c'=>"\17",'pre'=>1),
		'tfoot'=>array('c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>array('tbody','thead')),
		'th'=>array('c'=>"\0\0\10",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>array('td','th')),
		'thead'=>array('c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1),
		'time'=>array('c'=>"\7",'ac'=>"\4"),
		'title'=>array('c'=>"\20",'to'=>1,'b'=>1),
		'tr'=>array('c'=>"\200\0\0\0\4",'ac'=>"\0\40\10",'nt'=>1,'b'=>1,'cp'=>array('tr')),
		'track'=>array('c'=>"\0\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'u'=>array('c'=>"\7",'ac'=>"\4",'fe'=>1),
		'ul'=>array('c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'var'=>array('c'=>"\7",'ac'=>"\4"),
		'video'=>array('c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1),
		'wbr'=>array('c'=>"\5",'nt'=>1,'e'=>1,'v'=>1)
	);

	/*
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
			return "\0";

		$bitfield = self::$htmlElements[$elName][$k];

		foreach (\str_split($bitfield, 1) as $byteNumber => $char)
		{
			$byteValue = \ord($char);

			for ($bitNumber = 0; $bitNumber < 8; ++$bitNumber)
			{
				$bitValue = 1 << $bitNumber;

				if (!($byteValue & $bitValue))
					// The bit is not set
					continue;

				$n = $byteNumber * 8 + $bitNumber;

				// Test for an XPath condition for that category
				if (isset(self::$htmlElements[$elName][$k . $n]))
				{
					$xpath = self::$htmlElements[$elName][$k . $n];

					// If the XPath condition is not fulfilled...
					if (!$this->evaluate($xpath, $node))
					{
						// ...turn off the corresponding bit
						$byteValue ^= $bitValue;

						// Update the original bitfield
						$bitfield[$byteNumber] = \chr($byteValue);
					}
				}
			}
		}

		return $bitfield;
	}

	/*
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
			// Test the XPath condition
			if (!isset(self::$htmlElements[$elName][$propName . '0'])
			 || $this->evaluate(self::$htmlElements[$elName][$propName . '0'], $node))
				return \true;

		return \false;
	}

	/*
	* Test whether two bitfields have any bits in common
	*
	* @param  string $bitfield1
	* @param  string $bitfield2
	* @return bool
	*/
	protected static function match($bitfield1, $bitfield2)
	{
		return (\trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}