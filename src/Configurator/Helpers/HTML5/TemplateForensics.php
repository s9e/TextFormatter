<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers\HTML5;

use DOMDocument;
use DOMNode;
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
* @see  /scripts/patchTemplateForensics.php
*/
class TemplateForensics
{
	/**
	* @var string allowChild bitfield (all branches)
	*/
	protected $allowChildBitfield = "\0";

	/**
	* @var string Whether text nodes are allowed as children
	*/
	protected $allowText = true;

	/**
	* @var string Whether to automatically reopen this tag
	*/
	protected $autoReopen = false;

	/**
	* @var string OR-ed bitfield representing all of the categories used by this tag's templates
	*/
	protected $contentBitfield = "\0";

	/**
	* @var bool Whether to deny any descendants to this tag
	*/
	protected $denyAll = true;

	/**
	* @var string denyDescendant bitfield
	*/
	protected $denyDescendantBitfield = "\0";

	/**
	* @var DOMDocument Document containing all the templates associated with this tag, concatenated
	*/
	protected $dom;

	/**
	* @var bool Whether this tag renders non-whitespace text nodes at its root
	*/
	protected $hasRootText = false;

	/**
	* @var bool Whether this tag should be considered a block-level element
	*/
	protected $isBlock = false;

	/**
	* @var bool Whether all branches use the transparent content model (or more accurately, whether
	*           no branch uses a content model other than transparent)
	*/
	protected $isTransparent = true;

	/**
	* @var bool Whether all branches have an ancestor that is a void element
	*/
	protected $isVoid = true;

	/**
	* @var array Names of every last HTML element that precedes an <xsl:apply-templates/> node
	*/
	protected $leafNodes = array();

	/**
	* @var bool Whether any branch has an element that preserves whitespace by default (e.g. <pre>)
	*/
	protected $preservesWhitespace = false;

	/**
	* @var array Bitfield of the first HTML element of every branch
	*/
	protected $rootBitfields = array();

	/**
	* @var array Names of every HTML element that have no HTML parent
	*/
	protected $rootNodes = array();

	/**
	* @var DOMXPath XPath engine associated with $this->dom
	*/
	protected $xpath;

	/**
	* @param  string $xsl One single <xsl:template/> element
	* @return void
	*/
	public function __construct($xsl)
	{
		$this->dom = new DOMDocument;
		$this->dom->loadXML($xsl);

		$this->xpath = new DOMXPath($this->dom);

		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}

	/**
	* Whether this tag allows given tag as a child
	*
	* @param  self $child
	* @return bool
	*/
	public function allowsChild(self $child)
	{
		// Sometimes, a tag can technically be allowed as a child due to the transparent content
		// model but denied as a descendant
		if (!$this->allowsDescendant($child))
		{
			return false;
		}

		foreach ($child->rootBitfields as $rootBitfield)
		{
			if (!self::match($rootBitfield, $this->allowChildBitfield))
			{
				return false;
			}
		}

		if (!$this->allowText && $child->hasRootText)
		{
			return false;
		}

		return true;
	}

	/**
	* Whether this tag allows given tag as a descendant
	*
	* @param  self $descendant
	* @return bool
	*/
	public function allowsDescendant(self $descendant)
	{
		return !self::match($descendant->contentBitfield, $this->denyDescendantBitfield);
	}

	/**
	* Whether this tag allows text nodes as children
	*
	* @return bool
	*/
	public function allowsText()
	{
		return $this->allowText;
	}

	/**
	* Whether to automatically reopen this tag
	*
	* @return bool
	*/
	public function autoReopen()
	{
		return $this->autoReopen;
	}

	/**
	* Whether this tag automatically closes given parent tag
	*
	* @param  self $parent
	* @return bool
	*/
	public function closesParent(self $parent)
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
					// If any of this tag's root node closes one of the parent's leaf node, we
					// consider that this tag closes the other one
					return true;
				}
			}
		}

		return false;
	}

	/**
	* Whether this tag should deny any descendants
	*
	* @return bool
	*/
	public function denyAll()
	{
		return $this->denyAll;
	}

	/**
	* Whether this tag should be considered a block-level element
	*
	* @return bool
	*/
	public function isBlock()
	{
		return $this->isBlock;
	}

	/**
	* Whether this tag should use the transparent content model
	*
	* @return bool
	*/
	public function isTransparent()
	{
		return $this->isTransparent;
	}

	/**
	* Whether all branches have an ancestor that is a void element
	*
	* @return bool
	*/
	public function isVoid()
	{
		return $this->isVoid;
	}

	/**
	* Whether this tag preserves the whitespace in its descendants
	*
	* @return bool
	*/
	public function preservesWhitespace()
	{
		return $this->preservesWhitespace;
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
		}
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

			// If any root node is a block-level element, we'll mark the tag as such
			if (!empty(self::$htmlElements[$elName]['b']))
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

		if ($this->evaluate($query))
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
		$branchBitfields = array();

		/**
		* @var bool Whether this template should generate an autoReopen rules because all of the branches are entirely composed of elements from the adoption agency list
		*/
		$autoReopen = true;

		// For each <xsl:apply-templates/> element...
		foreach ($this->getXSLElements('apply-templates') as $applyTemplates)
		{
			// ...we retrieve all non-XSL ancestors
			$nodes = $this->xpath->query(
				'ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]',
				$applyTemplates
			);

			if (!$nodes->length)
			{
				// That tag might have an empty template for some reason, in which case there's
				// nothing to do here
				continue;
			}

			/**
			* @var string allowChild bitfield for current branch. Starts with the value associated
			*             with <span> in order to approximate a value if the whole branch uses the
			*             transparent content model
			*/
			$branchBitfield = self::$htmlElements['span']['ac'];

			/**
			* @var bool Whether this branch denies all non-text descendants
			*/
			$denyAll = false;

			/**
			* @var bool Whether this branch contains a void element
			*/
			$isVoid = false;

			foreach ($nodes as $node)
			{
				$elName = $node->localName;

				if (!isset(self::$htmlElements[$elName]))
				{
					// Unknown elements are treated as if they were a <span> element
					$elName = 'span';
				}

				// Test whether the element is void
				if (!empty(self::$htmlElements[$elName]['v']))
				{
					$isVoid = true;
				}

				// Test whether the element denies all descendants
				if (!empty(self::$htmlElements[$elName]['da']))
				{
					// Test the XPath condition
					if (!isset(self::$htmlElements[$elName]['da0'])
					 || $this->evaluate(self::$htmlElements[$elName]['da0'], $node))
					{
						$denyAll = true;
					}
				}

				if (empty(self::$htmlElements[$elName]['t']))
				{
					// If the element isn't transparent, we reset its bitfield
					$branchBitfield = "\0";

					// Also, it means that the tag itself isn't transparent
					$this->isTransparent = false;
				}

				// Test whether this element is on the adoption agency list
				if (empty(self::$htmlElements[$elName]['ar']))
				{
					$autoReopen = false;
				}

				// Test whether this branch preserves whitespace
				if (!empty(self::$htmlElements[$elName]['pre']))
				{
					$this->preservesWhitespace = true;
				}

				// Test whether this branch allows text nodes
				$allowText = empty(self::$htmlElements[$elName]['nt']);

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= $this->getBitfield($elName, 'ac', $node);

				// denyDescendant rules are cumulative
				$this->denyDescendantBitfield |= $this->getBitfield($elName, 'dd', $node);
			}

			// Add this branch's bitfield to the list
			$branchBitfields[] = $branchBitfield;

			// Save the name of the last node processed. Its actual name, not the "span" workaround
			$this->leafNodes[] = $node->localName;

			// If any branch disallows text, the tag disallows text
			if (!$allowText)
			{
				$this->allowText = false;
			}

			// If any branch does not deny all descendants, the tag does not deny all descendants
			if (!$denyAll)
			{
				$this->denyAll = false;
			}

			// If any branch is not void, the tag is not void
			if (!$isVoid)
			{
				$this->isVoid = false;
			}
		}

		// Now we take the bitfield of each branch and reduce them to a single ANDed bitfield
		if (!empty($branchBitfields))
		{
			$this->allowChildBitfield = $branchBitfields[0];

			foreach ($branchBitfields as $branchBitfield)
			{
				$this->allowChildBitfield &= $branchBitfield;
			}

			// Set the autoReopen property to our final value, but only if this tag had any branches
			$this->autoReopen = $autoReopen;
		}
	}

	/**
	* Evaluate a boolean XPath query
	*
	* @param  string  $query XPath query
	* @param  DOMNode $node  Context node
	* @return boolean
	*/
	protected function evaluate($query, DOMNode $node = null)
	{
		return $this->xpath->evaluate('boolean(' . $query . ')', $node);
	}

	/**
	* Get all XSL elements of given name
	*
	* @param  string      $elName XSL element's name, e.g. "apply-templates"
	* @return DOMNodeList
	*/
	protected function getXSLElements($elName)
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', $elName);
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
	*   "t" indicates that the element uses the transparent content model.
	*
	*   "da" indicates that the element rejects any descendants. (denyAll)
	*
	*   "v" indicates that the element is a void element.
	*
	*   "nt" indicates that the element does not accept text nodes.
	*
	*   "ar" indicates that the element is on the "adoption agency algorithm" list, which is used
	*   to automatically reopen tags when they are closed by an end tag of a different name.
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
		'a'=>array('c'=>"\17",'ac'=>"\0",'dd'=>"\10",'t'=>1,'ar'=>1),
		'abbr'=>array('c'=>"\7",'ac'=>"\4"),
		'address'=>array('c'=>"\3\10",'ac'=>"\1",'dd'=>"\100\14",'b'=>1,'cp'=>array('p')),
		'area'=>array('c'=>"\5",'nt'=>1,'da'=>1,'v'=>1),
		'article'=>array('c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'aside'=>array('c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'audio'=>array('c'=>"\217",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\100\2",'ac22'=>'not(@src)','ac25'=>'@src','t'=>1),
		'b'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'base'=>array('c'=>"\20",'nt'=>1,'da'=>1,'v'=>1,'b'=>1),
		'bdi'=>array('c'=>"\7",'ac'=>"\4"),
		'bdo'=>array('c'=>"\7",'ac'=>"\4"),
		'blockquote'=>array('c'=>"\43",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'body'=>array('c'=>"\40\200",'ac'=>"\1",'b'=>1),
		'br'=>array('c'=>"\5",'nt'=>1,'da'=>1,'v'=>1),
		'button'=>array('c'=>"\17",'ac'=>"\4",'dd'=>"\10"),
		'canvas'=>array('c'=>"\207",'ac'=>"\0",'t'=>1),
		'caption'=>array('c'=>"\0\1",'ac'=>"\1",'dd'=>"\0\0\0\4",'b'=>1),
		'cite'=>array('c'=>"\7",'ac'=>"\4"),
		'code'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'col'=>array('c'=>"\0\0\0\0\2",'nt'=>1,'da'=>1,'v'=>1,'b'=>1),
		'colgroup'=>array('c'=>"\0\1",'ac'=>"\0\0\0\0\2",'ac33'=>'not(@span)','nt'=>1,'da'=>1,'da0'=>'@span','b'=>1),
		'command'=>array('c'=>"\25",'nt'=>1,'da'=>1,'v'=>1),
		'datalist'=>array('c'=>"\5",'ac'=>"\4\0\200"),
		'dd'=>array('c'=>"\0\0\4",'ac'=>"\1",'b'=>1,'cp'=>array('dd','dt')),
		'del'=>array('c'=>"\5",'ac'=>"\0",'t'=>1),
		'details'=>array('c'=>"\53",'ac'=>"\1\0\40",'b'=>1),
		'dfn'=>array('c'=>"\7\0\0\0\1",'ac'=>"\4",'dd'=>"\0\0\0\0\1"),
		'dialog'=>array('c'=>"\41",'ac'=>"\1",'b'=>1),
		'div'=>array('c'=>"\3",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'dl'=>array('c'=>"\3",'ac'=>"\0\0\4",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'dt'=>array('c'=>"\0\0\4",'ac'=>"\1",'dd'=>"\100\104",'b'=>1,'cp'=>array('dd','dt')),
		'em'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'embed'=>array('c'=>"\217",'nt'=>1,'da'=>1,'v'=>1),
		'fieldset'=>array('c'=>"\43",'ac'=>"\1\0\0\1",'b'=>1,'cp'=>array('p')),
		'figcaption'=>array('c'=>"\0\0\0\0\20",'ac'=>"\1",'b'=>1),
		'figure'=>array('c'=>"\43",'ac'=>"\1\0\0\0\20",'b'=>1),
		'footer'=>array('c'=>"\3\110",'ac'=>"\1",'dd'=>"\0\100",'b'=>1,'cp'=>array('p')),
		'form'=>array('c'=>"\3\0\0\200",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>array('p')),
		'h1'=>array('c'=>"\103\2",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h2'=>array('c'=>"\103\2",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h3'=>array('c'=>"\103\2",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h4'=>array('c'=>"\103\2",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h5'=>array('c'=>"\103\2",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'h6'=>array('c'=>"\103\2",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'head'=>array('c'=>"\0\200",'ac'=>"\20",'nt'=>1,'b'=>1),
		'header'=>array('c'=>"\3\110",'ac'=>"\1",'dd'=>"\0\100",'b'=>1,'cp'=>array('p')),
		'hgroup'=>array('c'=>"\103",'ac'=>"\0\2",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'hr'=>array('c'=>"\1",'nt'=>1,'da'=>1,'v'=>1,'b'=>1,'cp'=>array('p')),
		'html'=>array('c'=>"\0",'ac'=>"\0\200",'nt'=>1,'b'=>1),
		'i'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'iframe'=>array('c'=>"\217",'nt'=>1,'da'=>1),
		'img'=>array('c'=>"\217",'c3'=>'@usemap','nt'=>1,'da'=>1,'v'=>1),
		'input'=>array('c'=>"\17",'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"','nt'=>1,'da'=>1,'v'=>1),
		'ins'=>array('c'=>"\7",'ac'=>"\0",'t'=>1),
		'kbd'=>array('c'=>"\7",'ac'=>"\4"),
		'keygen'=>array('c'=>"\17",'nt'=>1,'da'=>1,'v'=>1),
		'label'=>array('c'=>"\17\0\0\40",'ac'=>"\4",'dd'=>"\0\0\0\40"),
		'legend'=>array('c'=>"\0\0\0\1",'ac'=>"\4",'b'=>1),
		'li'=>array('c'=>"\0\0\0\0\10",'ac'=>"\1",'b'=>1,'cp'=>array('li')),
		'link'=>array('c'=>"\20",'nt'=>1,'da'=>1,'v'=>1,'b'=>1),
		'map'=>array('c'=>"\7",'ac'=>"\0",'t'=>1),
		'mark'=>array('c'=>"\7",'ac'=>"\4"),
		'menu'=>array('c'=>"\13",'c3'=>'@type="toolbar"','c1'=>'@type="toolbar" or @type="list"','ac'=>"\1\0\0\0\10",'b'=>1,'cp'=>array('p')),
		'meta'=>array('c'=>"\20",'nt'=>1,'da'=>1,'v'=>1,'b'=>1),
		'meter'=>array('c'=>"\7\20\0\20",'ac'=>"\4",'dd'=>"\0\0\0\20"),
		'nav'=>array('c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'noscript'=>array('c'=>"\25\0\20",'ac'=>"\0",'dd'=>"\0\0\20",'t'=>1),
		'object'=>array('c'=>"\217",'c3'=>'@usemap','ac'=>"\11\0\0\10"),
		'ol'=>array('c'=>"\3",'ac'=>"\0\0\0\0\10",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'optgroup'=>array('c'=>"\0\40",'ac'=>"\0\0\200",'nt'=>1,'b'=>1,'cp'=>array('optgroup','option')),
		'option'=>array('c'=>"\0\40\200",'b'=>1,'cp'=>array('option')),
		'output'=>array('c'=>"\7",'ac'=>"\4"),
		'p'=>array('c'=>"\3",'ac'=>"\4",'b'=>1,'cp'=>array('p')),
		'param'=>array('c'=>"\0\0\0\10",'nt'=>1,'da'=>1,'v'=>1,'b'=>1),
		'pre'=>array('c'=>"\3",'ac'=>"\4",'pre'=>1,'b'=>1,'cp'=>array('p')),
		'progress'=>array('c'=>"\7\20\10",'ac'=>"\4",'dd'=>"\0\0\10"),
		'q'=>array('c'=>"\7",'ac'=>"\4"),
		'rp'=>array('c'=>"\0\0\2",'ac'=>"\4",'b'=>1,'cp'=>array('rp','rt')),
		'rt'=>array('c'=>"\0\0\2",'ac'=>"\4",'b'=>1,'cp'=>array('rp','rt')),
		'ruby'=>array('c'=>"\7\0\0\100",'ac'=>"\4\0\2",'dd'=>"\0\0\0\100"),
		's'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'samp'=>array('c'=>"\7",'ac'=>"\4"),
		'script'=>array('c'=>"\25",'nt'=>1,'da'=>1),
		'section'=>array('c'=>"\3\4",'ac'=>"\1",'b'=>1,'cp'=>array('p')),
		'select'=>array('c'=>"\17",'ac'=>"\0\40",'nt'=>1),
		'small'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'source'=>array('c'=>"\0\0\100",'nt'=>1,'da'=>1,'v'=>1,'b'=>1),
		'span'=>array('c'=>"\7",'ac'=>"\4"),
		'strong'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'style'=>array('c'=>"\21",'c0'=>'@scoped','nt'=>1,'da'=>1,'b'=>1),
		'sub'=>array('c'=>"\7",'ac'=>"\4"),
		'summary'=>array('c'=>"\0\0\40",'ac'=>"\4",'b'=>1),
		'sup'=>array('c'=>"\7",'ac'=>"\4"),
		'table'=>array('c'=>"\3\0\0\4",'ac'=>"\0\1",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'tbody'=>array('c'=>"\0\1",'ac'=>"\0\0\0\0\4",'nt'=>1,'b'=>1,'cp'=>array('tbody','tfoot','thead')),
		'td'=>array('c'=>"\40\0\1",'ac'=>"\1",'b'=>1,'cp'=>array('td','th')),
		'textarea'=>array('c'=>"\17",'pre'=>1),
		'tfoot'=>array('c'=>"\0\1",'ac'=>"\0\0\0\0\4",'nt'=>1,'b'=>1,'cp'=>array('tbody','thead')),
		'th'=>array('c'=>"\0\0\1",'ac'=>"\1",'dd'=>"\100\104",'b'=>1,'cp'=>array('td','th')),
		'thead'=>array('c'=>"\0\1",'ac'=>"\0\0\0\0\4",'nt'=>1,'b'=>1),
		'time'=>array('c'=>"\7",'ac'=>"\4"),
		'title'=>array('c'=>"\20",'b'=>1),
		'tr'=>array('c'=>"\0\1\0\0\4",'ac'=>"\0\0\1",'nt'=>1,'b'=>1,'cp'=>array('tr')),
		'track'=>array('c'=>"\0\0\0\2",'nt'=>1,'da'=>1,'v'=>1,'b'=>1),
		'u'=>array('c'=>"\7",'ac'=>"\4",'ar'=>1),
		'ul'=>array('c'=>"\3",'ac'=>"\0\0\0\0\10",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'var'=>array('c'=>"\7",'ac'=>"\4"),
		'video'=>array('c'=>"\217",'c3'=>'@controls','ac'=>"\0\0\100\2",'ac22'=>'not(@src)','ac25'=>'@src','t'=>1),
		'wbr'=>array('c'=>"\5",'nt'=>1,'da'=>1,'v'=>1)
	);

	/**
	* Get the bitfield value for a given element name in a given context
	*
	* @param  string  $elName Name of the HTML element
	* @param  string  $k      Bitfield name: either 'c', 'ac' or 'dd'
	* @param  DOMNode $node   Context node (not necessarily the same as $elName)
	* @return string
	*/
	protected function getBitfield($elName, $k, DOMNode $node)
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
					$xpath = self::$htmlElements[$elName][$k . $n];

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