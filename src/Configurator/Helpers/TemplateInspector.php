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
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var string[] allowChild bitfield for each branch
	*/
	protected $allowChildBitfields = [];

	/**
	* @var bool Whether elements are allowed as children
	*/
	protected $allowsChildElements;

	/**
	* @var bool Whether text nodes are allowed as children
	*/
	protected $allowsText;

	/**
	* @var array[] Array of array of DOMElement instances
	*/
	protected $branches;

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
	protected $hasRootText;

	/**
	* @var bool Whether this template should be considered a block-level element
	*/
	protected $isBlock = false;

	/**
	* @var bool Whether the template uses the "empty" content model
	*/
	protected $isEmpty;

	/**
	* @var bool Whether this template adds to the list of active formatting elements
	*/
	protected $isFormattingElement;

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
	protected $isVoid;

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
	* @param string $template Template content
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

		return ($this->allowsText || !$child->hasRootText);
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
		return ($this->allowsChildElements || !$descendant->hasElements);
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
	* Evaluate an XPath expression
	*
	* @param  string     $expr XPath expression
	* @param  DOMElement $node Context node
	* @return mixed
	*/
	public function evaluate($expr, DOMElement $node = null)
	{
		return $this->xpath->evaluate($expr, $node);
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
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]';
		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= $this->getBitfield($node, 'c');
			$this->hasElements = true;
		}

		// Test whether this template is passthrough
		$this->isPassthrough = (bool) $this->evaluate('count(//xsl:apply-templates)');
	}

	/**
	* Records the HTML elements (and their bitfield) rendered at the root of the template
	*/
	protected function analyseRootNodes()
	{
		// Get every non-XSL element with no non-XSL ancestor. This should return us the first
		// HTML element of every branch
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]'
		       . '[not(ancestor::*[namespace-uri() != "' . self::XMLNS_XSL . '"])]';
		foreach ($this->xpath->query($query) as $node)
		{
			// Save the actual name of the root node
			$this->rootNodes[] = $node->localName;

			// If any root node is a block-level element, we'll mark the template as such
			if ($this->elementIsBlock($node))
			{
				$this->isBlock = true;
			}

			$this->rootBitfields[] = $this->getBitfield($node, 'c');
		}

		// Test for non-whitespace text nodes at the root. For that we need a predicate that filters
		// out: nodes with a non-XSL ancestor,
		$predicate = '[not(ancestor::*[namespace-uri() != "' . self::XMLNS_XSL . '"])]';

		// ..and nodes with an <xsl:attribute/>, <xsl:comment/> or <xsl:variable/> ancestor
		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';

		$query = '//text()[normalize-space() != ""]' . $predicate
		       . '|'
		       . '//xsl:text[normalize-space() != ""]' . $predicate
		       . '|'
		       . '//xsl:value-of' . $predicate;

		$this->hasRootText = (bool) $this->evaluate('count(' . $query . ')');
	}

	/**
	* Analyses each branch that leads to an <xsl:apply-templates/> tag
	*/
	protected function analyseBranches()
	{
		$this->branches = [];
		foreach ($this->xpath->query('//xsl:apply-templates') as $applyTemplates)
		{
			$query            = 'ancestor::*[namespace-uri() != "' . self::XMLNS_XSL . '"]';
			$this->branches[] = iterator_to_array($this->xpath->query($query, $applyTemplates));
		}

		$this->computeAllowsChildElements();
		$this->computeAllowsText();
		$this->computeBitfields();
		$this->computeFormattingElement();
		$this->computeIsEmpty();
		$this->computeIsTransparent();
		$this->computeIsVoid();
		$this->computePreservesNewLines();
		$this->storeLeafNodes();
	}

	/**
	* Test whether any branch of this template has an element that has given property
	*
	* @param  string $propName
	* @return bool
	*/
	protected function anyBranchHasProperty($propName)
	{
		foreach ($this->branches as $branch)
		{
			foreach ($branch as $element)
			{
				if ($this->hasProperty($element->nodeName, $propName, $element))
				{
					return true;
				}
			}
		}

		return false;
	}

	/**
	* Compute the allowChildBitfields and denyDescendantBitfield properties
	*
	* @return void
	*/
	protected function computeBitfields()
	{
		if (empty($this->branches))
		{
			$this->allowChildBitfields = ["\0"];

			return;
		}
		foreach ($this->branches as $branch)
		{
			/**
			* @var string allowChild bitfield for current branch. Starts with the value associated
			*             with <div> in order to approximate a value if the whole branch uses the
			*             transparent content model
			*/
			$branchBitfield = self::$htmlElements['div']['ac'];

			foreach ($branch as $element)
			{
				$elName = $element->localName;
				if (!$this->hasProperty($elName, 't', $element))
				{
					// If the element isn't transparent, we reset its bitfield
					$branchBitfield = "\0";
				}

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= $this->getBitfield($element, 'ac');

				// denyDescendant rules are cumulative
				$this->denyDescendantBitfield |= $this->getBitfield($element, 'dd');
			}

			// Add this branch's bitfield to the list
			$this->allowChildBitfields[] = $branchBitfield;
		}
	}

	/**
	* Compute the allowsChildElements property
	*
	* A template allows child Elements if it has at least one xsl:apply-templates and none of its
	* ancestors have the text-only ("to") property
	*
	* @return void
	*/
	protected function computeAllowsChildElements()
	{
		$this->allowsChildElements = ($this->anyBranchHasProperty('to')) ? false : !empty($this->branches);
	}

	/**
	* Compute the allowsText property
	*
	* A template is said to allow text if none of the leaf elements disallow text
	*
	* @return void
	*/
	protected function computeAllowsText()
	{
		foreach (array_filter($this->branches) as $branch)
		{
			$element = end($branch);
			if ($this->hasProperty($element->nodeName, 'nt', $element))
			{
				$this->allowsText = false;

				return;
			}
		}
		$this->allowsText = true;
	}

	/**
	* Compute the isFormattingElement property
	*
	* A template is said to be a formatting element if all (non-zero) of its branches are entirely
	* composed of formatting elements
	*
	* @return void
	*/
	protected function computeFormattingElement()
	{
		foreach ($this->branches as $branch)
		{
			foreach ($branch as $element)
			{
				if (!$this->hasProperty($element->nodeName, 'fe', $element) && !$this->isFormattingSpan($element))
				{
					$this->isFormattingElement = false;

					return;
				}
			}
		}
		$this->isFormattingElement = (bool) count(array_filter($this->branches));
	}

	/**
	* Compute the isEmpty property
	*
	* A template is said to be empty if it has no xsl:apply-templates elements or any there is a empty
	* element ancestor to an xsl:apply-templates element
	*
	* @return void
	*/
	protected function computeIsEmpty()
	{
		$this->isEmpty = ($this->anyBranchHasProperty('e')) || empty($this->branches);
	}

	/**
	* Compute the isTransparent property
	*
	* A template is said to be transparent if it has at least one branch and no non-transparent
	* elements in its path
	*
	* @return void
	*/
	protected function computeIsTransparent()
	{
		foreach ($this->branches as $branch)
		{
			foreach ($branch as $element)
			{
				if (!$this->hasProperty($element->nodeName, 't', $element))
				{
					$this->isTransparent = false;

					return;
				}
			}
		}
		$this->isTransparent = !empty($this->branches);
	}

	/**
	* Compute the isVoid property
	*
	* A template is said to be void if it has no xsl:apply-templates elements or any there is a void
	* element ancestor to an xsl:apply-templates element
	*
	* @return void
	*/
	protected function computeIsVoid()
	{
		$this->isVoid = ($this->anyBranchHasProperty('v')) || empty($this->branches);
	}

	/**
	* Compute the preservesNewLines property
	*
	* @return void
	*/
	protected function computePreservesNewLines()
	{
		foreach ($this->branches as $branch)
		{
			$style = '';
			foreach ($branch as $element)
			{
				$style .= $this->getStyle($element, true);
			}

			if (preg_match('(.*white-space\\s*:\\s*(no|pre))is', $style, $m) && strtolower($m[1]) === 'pre')
			{
				$this->preservesNewLines = true;

				return;
			}
		}
		$this->preservesNewLines = false;
	}

	/**
	* Test whether given element is a block-level element
	*
	* @param  DOMElement $element
	* @return bool
	*/
	protected function elementIsBlock(DOMElement $element)
	{
		$style = $this->getStyle($element);
		if (preg_match('(\\bdisplay\\s*:\\s*block)i', $style))
		{
			return true;
		}
		if (preg_match('(\\bdisplay\\s*:\\s*(?:inli|no)ne)i', $style))
		{
			return false;
		}

		return $this->hasProperty($element->nodeName, 'b', $element);
	}

	/**
	* Retrieve and return the inline style assigned to given element
	*
	* @param  DOMElement $node Context node
	* @param  bool       $deep Whether to retrieve the content of all xsl:attribute descendants
	* @return string
	*/
	protected function getStyle(DOMElement $node, $deep = false)
	{
		$style = '';
		if ($this->hasProperty($node->nodeName, 'pre', $node))
		{
			$style .= 'white-space:pre;';
		}
		$style .= $node->getAttribute('style');

		// Add the content of any descendant/child xsl:attribute named "style"
		$query = (($deep) ? './/' : './') . 'xsl:attribute[@name="style"]';
		foreach ($this->xpath->query($query, $node) as $attribute)
		{
			$style .= ';' . $attribute->textContent;
		}

		return $style;
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
	* Store the names of every leaf node
	*
	* A leaf node is defined as the closest non-XSL ancestor to an xsl:apply-templates element
	*
	* @return void
	*/
	protected function storeLeafNodes()
	{
		foreach (array_filter($this->branches) as $branch)
		{
			$this->leafNodes[] = end($branch)->nodeName;
		}
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
		'abbr'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'address'=>['c'=>"\3\40",'ac'=>"\1",'dd'=>"\0\45",'b'=>1,'cp'=>['p']],
		'article'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'aside'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'audio'=>['c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\0\104",'ac26'=>'not(@src)','dd'=>"\0\0\0\0\0\2",'dd41'=>'@src','t'=>1],
		'b'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'base'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'bdi'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'bdo'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'blockquote'=>['c'=>"\203",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'body'=>['c'=>"\200\0\4",'ac'=>"\1",'dd'=>"\0",'b'=>1],
		'br'=>['c'=>"\5",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'button'=>['c'=>"\117",'ac'=>"\4",'dd'=>"\10"],
		'canvas'=>['c'=>"\47",'ac'=>"\0",'dd'=>"\0",'t'=>1],
		'caption'=>['c'=>"\0\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1],
		'cite'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'code'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'col'=>['c'=>"\0\0\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'colgroup'=>['c'=>"\0\2",'ac'=>"\0\0\20",'ac20'=>'not(@span)','dd'=>"\0",'nt'=>1,'e'=>1,'e0'=>'@span','b'=>1],
		'data'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'datalist'=>['c'=>"\5",'ac'=>"\4\200\0\10",'dd'=>"\0"],
		'dd'=>['c'=>"\0\0\200",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['dd','dt']],
		'del'=>['c'=>"\5",'ac'=>"\0",'dd'=>"\0",'t'=>1],
		'details'=>['c'=>"\213",'ac'=>"\1\0\0\2",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'dfn'=>['c'=>"\7\0\0\0\40",'ac'=>"\4",'dd'=>"\0\0\0\0\40"],
		'div'=>['c'=>"\3",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'dl'=>['c'=>"\3",'c1'=>'dt and dd','ac'=>"\0\200\200",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'dt'=>['c'=>"\0\0\200",'ac'=>"\1",'dd'=>"\0\5\0\40",'b'=>1,'cp'=>['dd','dt']],
		'em'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'embed'=>['c'=>"\57",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'fieldset'=>['c'=>"\303",'ac'=>"\1\0\0\20",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'figcaption'=>['c'=>"\0\0\0\0\0\4",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'figure'=>['c'=>"\203",'ac'=>"\1\0\0\0\0\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'footer'=>['c'=>"\3\40",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'form'=>['c'=>"\3\0\0\0\20",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['p']],
		'h1'=>['c'=>"\3\1",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h2'=>['c'=>"\3\1",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h3'=>['c'=>"\3\1",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h4'=>['c'=>"\3\1",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h5'=>['c'=>"\3\1",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h6'=>['c'=>"\3\1",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'head'=>['c'=>"\0\0\4",'ac'=>"\20",'dd'=>"\0",'nt'=>1,'b'=>1],
		'header'=>['c'=>"\3\40\0\40",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'hr'=>['c'=>"\1\100",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>['p']],
		'html'=>['c'=>"\0",'ac'=>"\0\0\4",'dd'=>"\0",'nt'=>1,'b'=>1],
		'i'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'iframe'=>['c'=>"\57",'ac'=>"\4",'dd'=>"\0"],
		'img'=>['c'=>"\57\20\10",'c3'=>'@usemap','ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'input'=>['c'=>"\17\20",'c3'=>'@type!="hidden"','c12'=>'@type!="hidden" or @type="hidden"','c1'=>'@type!="hidden"','ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'ins'=>['c'=>"\7",'ac'=>"\0",'dd'=>"\0",'t'=>1],
		'kbd'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'keygen'=>['c'=>"\117",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'label'=>['c'=>"\17\20\0\0\4",'ac'=>"\4",'dd'=>"\0\0\1\0\4"],
		'legend'=>['c'=>"\0\0\0\20",'ac'=>"\4",'dd'=>"\0",'b'=>1],
		'li'=>['c'=>"\0\0\0\0\200",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['li']],
		'link'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'main'=>['c'=>"\3\0\0\0\10",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'mark'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'media element'=>['c'=>"\0\0\0\0\0\2",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'b'=>1],
		'menu'=>['c'=>"\1\100",'ac'=>"\0\300",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'menuitem'=>['c'=>"\0\100",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meta'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meter'=>['c'=>"\7\0\1\0\2",'ac'=>"\4",'dd'=>"\0\0\0\0\2"],
		'nav'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\10",'b'=>1,'cp'=>['p']],
		'noscript'=>['c'=>"\25",'ac'=>"\0",'dd'=>"\0",'nt'=>1],
		'object'=>['c'=>"\147",'ac'=>"\0\0\0\0\1",'dd'=>"\0",'t'=>1],
		'ol'=>['c'=>"\3",'c1'=>'li','ac'=>"\0\200\0\0\200",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'optgroup'=>['c'=>"\0\0\2",'ac'=>"\0\200\0\10",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['optgroup','option']],
		'option'=>['c'=>"\0\0\2\10",'ac'=>"\0",'dd'=>"\0",'b'=>1,'cp'=>['option']],
		'output'=>['c'=>"\107",'ac'=>"\4",'dd'=>"\0"],
		'p'=>['c'=>"\3",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'param'=>['c'=>"\0\0\0\0\1",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'picture'=>['c'=>"\45",'ac'=>"\0\200\10",'dd'=>"\0",'nt'=>1],
		'pre'=>['c'=>"\3",'ac'=>"\4",'dd'=>"\0",'pre'=>1,'b'=>1,'cp'=>['p']],
		'progress'=>['c'=>"\7\0\1\1",'ac'=>"\4",'dd'=>"\0\0\0\1"],
		'q'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'rb'=>['c'=>"\0\10",'ac'=>"\4",'dd'=>"\0",'b'=>1],
		'rp'=>['c'=>"\0\10\100",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['rp','rt']],
		'rt'=>['c'=>"\0\10\100",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['rp','rt']],
		'rtc'=>['c'=>"\0\10",'ac'=>"\4\0\100",'dd'=>"\0",'b'=>1],
		'ruby'=>['c'=>"\7",'ac'=>"\4\10",'dd'=>"\0"],
		's'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'samp'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'script'=>['c'=>"\25\200",'ac'=>"\0",'dd'=>"\0",'to'=>1],
		'section'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'select'=>['c'=>"\117",'ac'=>"\0\200\2",'dd'=>"\0",'nt'=>1],
		'small'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'source'=>['c'=>"\0\0\10\4",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'span'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'strong'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'style'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'to'=>1,'b'=>1],
		'sub'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'summary'=>['c'=>"\0\0\0\2",'ac'=>"\4\1",'dd'=>"\0",'b'=>1],
		'sup'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'table'=>['c'=>"\3\0\0\200",'ac'=>"\0\202",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'tbody'=>['c'=>"\0\2",'ac'=>"\0\200\0\0\100",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['tbody','td','tfoot','th','thead','tr']],
		'td'=>['c'=>"\200\0\40",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['td','th']],
		'template'=>['c'=>"\25\200\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1],
		'textarea'=>['c'=>"\117",'ac'=>"\0",'dd'=>"\0",'pre'=>1,'to'=>1],
		'tfoot'=>['c'=>"\0\2",'ac'=>"\0\200\0\0\100",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['tbody','td','th','thead','tr']],
		'th'=>['c'=>"\0\0\40",'ac'=>"\1",'dd'=>"\0\5\0\40",'b'=>1,'cp'=>['td','th']],
		'thead'=>['c'=>"\0\2",'ac'=>"\0\200\0\0\100",'dd'=>"\0",'nt'=>1,'b'=>1],
		'time'=>['c'=>"\7",'ac'=>"\4",'ac2'=>'@datetime','dd'=>"\0"],
		'title'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'to'=>1,'b'=>1],
		'tr'=>['c'=>"\0\2\0\0\100",'ac'=>"\0\200\40",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['td','th','tr']],
		'track'=>['c'=>"\0\0\0\100",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'u'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'ul'=>['c'=>"\3",'c1'=>'li','ac'=>"\0\200\0\0\200",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'var'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'video'=>['c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\0\104",'ac26'=>'not(@src)','dd'=>"\0\0\0\0\0\2",'dd41'=>'@src','t'=>1],
		'wbr'=>['c'=>"\5",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1]
	];

	/**
	* Get the bitfield value for a given element in a given context
	*
	* @param  DOMElement $element Context node
	* @param  string     $k       Bitfield name: either 'c', 'ac' or 'dd'
	* @return string
	*/
	protected function getBitfield(DOMElement $element, $k)
	{
		$elName = $element->nodeName;
		if (!isset(self::$htmlElements[$elName]))
		{
			$elName = 'span';
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
					if (!$this->evaluate($xpath, $element))
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
			 || $this->evaluate('boolean(' . self::$htmlElements[$elName][$propName . '0'] . ')', $node))
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