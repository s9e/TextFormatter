<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

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
	* @var string Default bitfield used at the root of a branch
	*/
	protected $defaultBranchBitfield;

	/**
	* @var string denyDescendant bitfield
	*/
	protected $denyDescendantBitfield = "\0";

	/**
	* @var \DOMDocument Document containing the template
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
	* @var array Last HTML element that precedes an <xsl:apply-templates/> node
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
	* @var array Every HTML element that has no HTML parent
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
		$this->dom   = TemplateLoader::load($template);
		$this->xpath = new DOMXPath($this->dom);

		$this->defaultBranchBitfield = ElementInspector::getAllowChildBitfield($this->dom->createElement('div'));

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
		// Test whether any of this template's root nodes closes any of given template's leaf nodes
		foreach ($this->rootNodes as $rootNode)
		{
			foreach ($parent->leafNodes as $leafNode)
			{
				if (ElementInspector::closesParent($rootNode, $leafNode))
				{
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
			$this->contentBitfield |= ElementInspector::getCategoryBitfield($node);
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
			// Store the root node of this branch
			$this->rootNodes[] = $node;

			// If any root node is a block-level element, we'll mark the template as such
			if ($this->elementIsBlock($node))
			{
				$this->isBlock = true;
			}

			$this->rootBitfields[] = ElementInspector::getCategoryBitfield($node);
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
	* @param  string $methodName
	* @return bool
	*/
	protected function anyBranchHasProperty($methodName)
	{
		foreach ($this->branches as $branch)
		{
			foreach ($branch as $element)
			{
				if (ElementInspector::$methodName($element))
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
			$branchBitfield = $this->defaultBranchBitfield;

			foreach ($branch as $element)
			{
				if (!ElementInspector::isTransparent($element))
				{
					// If the element isn't transparent, we reset its bitfield
					$branchBitfield = "\0";
				}

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= ElementInspector::getAllowChildBitfield($element);

				// denyDescendant rules are cumulative
				$this->denyDescendantBitfield |= ElementInspector::getDenyDescendantBitfield($element);
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
		$this->allowsChildElements = ($this->anyBranchHasProperty('isTextOnly')) ? false : !empty($this->branches);
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
			if (ElementInspector::disallowsText(end($branch)))
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
				if (!ElementInspector::isFormattingElement($element) && !$this->isFormattingSpan($element))
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
		$this->isEmpty = ($this->anyBranchHasProperty('isEmpty')) || empty($this->branches);
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
				if (!ElementInspector::isTransparent($element))
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
		$this->isVoid = ($this->anyBranchHasProperty('isVoid')) || empty($this->branches);
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

		return ElementInspector::isBlock($element);
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
		if (ElementInspector::preservesWhitespace($node))
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
			$this->leafNodes[] = end($branch);
		}
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