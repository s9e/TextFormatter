<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Helpers\HTML5;

use DOMXPath;
use SimpleXMLElement;
use s9e\TextFormatter\ConfigBuilder\Collections\Templateset;

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
	* @var string OR-ed bitfield representing all of the categories used by this tag's templates
	*/
	protected $contentBitfield = "\0";

	/**
	* @var string denyDescendant bitfield
	*/
	protected $denyDescendantBitfield = "\0";

	/**
	* @var bool Whether this tag renders non-whitespace text nodes at its root
	*/
	protected $hasRootText = false;

	/**
	* @var bool Whether all branches use the transparent content model (or more accurately, whether
	*           no branch uses a content model other than transparent)
	*/
	protected $isTransparent = true;

	/**
	* @var array Names of every last HTML element that precedes an <xsl:apply-templates/> node
	*/
	protected $leafNodes = array();

	/**
	* @var SimpleXMLElement Node containing all the templates associated with this tag, concatenated
	*/
	protected $node;

	/**
	* @var array Bitfield of the first HTML element of every branch
	*/
	protected $rootBitfields = array();

	/**
	* @var array Names of every HTML element that have no HTML parent
	*/
	protected $rootNodes = array();

	/**
	* @param  string $xsl One single <xsl:template/> element
	* @return void
	*/
	public function __construct($xsl)
	{
		$this->node = simplexml_load_string($xsl);

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
	* Whether this tag should use the transparent content model
	*
	* @return bool
	*/
	public function isTransparent()
	{
		return $this->isTransparent;
	}

	/**
	* Analyses the content of the whole template and set $this->contentBitfield accordingly
	*/
	protected function analyseContent()
	{
		// Get all non-XSL elements
		$nodes = $this->node->xpath('//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]');

		foreach ($nodes as $node)
		{
			$nodeName = $node->getName();

			if (isset(self::$htmlElements[$nodeName]))
			{
				$this->contentBitfield |= self::getBitfield($nodeName, 'c', $node);
			}
		}
	}

	/**
	* Records the HTML elements (and their bitfield) rendered at the root of the template
	*/
	protected function analyseRootNodes()
	{
		// Get every non-XSL element with no non-XSL ancestor. This should return us the first
		// HTML element of every branch
		$nodes = $this->node->xpath('//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"][not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]');

		foreach ($nodes as $node)
		{
			$nodeName = $node->getName();

			// Save the actual name of the root node
			$this->rootNodes[] = $nodeName;

			if (!isset(self::$htmlElements[$nodeName]))
			{
				// Unknown elements are treated as if they were a <span> element
				$nodeName = 'span';
			}

			$this->rootBitfields[] = self::getBitfield($nodeName, 'c', $node);
		}

		// Test for non-whitespace text nodes at the root. For that we need a predicate that filters
		// out: nodes with a non-XSL ancestor,
		$predicate = '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';

		// nodes with an <xsl:attribute/>, <xsl:comment/> or <xsl:variable/> ancestor
		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';

		$xpath = '//text()' . $predicate
		       . '|'
		       . '//xsl:text' . $predicate
		       . '|'
		       . '//xsl:value-of' . $predicate;

		if (count($this->node->xpath($xpath)))
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

		// For each <xsl:apply-templates/> element...
		foreach ($this->node->xpath('//xsl:apply-templates') as $at)
		{
			// ...we retrieve all non-XSL ancestors
			$nodes = $at->xpath('ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]');

			if (empty($nodes))
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

			foreach ($nodes as $node)
			{
				$nodeName = $node->getName();

				if (!isset(self::$htmlElements[$nodeName]))
				{
					// Unknown elements are treated as if they were a <span> element
					$nodeName = 'span';
				}

				if (empty(self::$htmlElements[$nodeName]['t']))
				{
					// If the element isn't transparent, we reset its bitfield
					$branchBitfield = "\0";

					// Also, it means that the tag itself isn't transparent
					$this->isTransparent = false;
				}

				// Test whether this branch allows text nodes
				$allowText = empty(self::$htmlElements[$nodeName]['nt']);

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= self::getBitfield($nodeName, 'ac', $node);

				// denyDescendant rules are cumulative
				$this->denyDescendantBitfield |= self::getBitfield($nodeName, 'dd', $node);
			}

			$branchBitfields[] = $branchBitfield;

			// Save the name of the last node processed. Its actual name, not the "span" workaround
			$this->leafNodes[] = $node->getName();

			// If any branch disallows text, the tag disallows text
			if (!$allowText)
			{
				$this->allowText = false;
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
		}
	}

	/**
	* "What is this?" you might ask. This is basically a compressed version of the HTML5 content
	* models, with some liberties taken.
	*
	* For each element, up to three bitfields are defined: "c", "ac" and "dd". Bitfields are stored
	* as a number for convenience.
	*
	* "c" represents the categories the element belongs to. The categories are comprised of HTML5
	* content models (such as "phrasing content" or "interactive content") plus a few special
	* categories created dynamically (parts of the specs refer to "a group of X and Y elements"
	* rather than a specific content model, in which case a special category is formed for those
	* elements.)
	*
	* "ac" represents the categories that are allowed as children of given element.
	*
	* "dd" represents the categories that must not appear as a descendant of given element.
	*
	* Sometimes, HTML5 specifies some restrictions on when an element can accept certain children,
	* or what categories the element belongs to. For example, an <img> element is only part of the
	* "interactive content" category if it has a "usemap" attribute. Those restrictions are
	* expressed as an XPath expression and stored using the concatenation of the key of the bitfield
	* plus the bit number of the category. For instance, if "interactive content" got assigned to
	* bit 2, the definition of the <img> element will contain a key "c2" with value "@usemap".
	*
	* There is a special content model defined in HTML5, the "transparent" content model. If an
	* element uses the "transparent" content model, the key "t" is non-empty (set to 1.)
	*
	* In addition, HTML5 defines "optional end tag" rules, where one element automatically closes
	* its predecessor. Those are used to generate closeParent rules and are stored in the "cp" key.
	*
	* @var array
	* @see /scripts/patchTemplateForensics.php
	*/
	protected static $htmlElements = array(
		'a'=>array('c'=>"\17",'ac'=>"\0",'dd'=>"\10",'t'=>1),
		'abbr'=>array('c'=>"\7",'ac'=>"\4"),
		'address'=>array('c'=>"\3\4",'ac'=>"\1",'dd'=>"\20\6",'cp'=>array('p')),
		'area'=>array('c'=>"\5",'nt'=>1),
		'article'=>array('c'=>"\3\2",'ac'=>"\1",'cp'=>array('p')),
		'aside'=>array('c'=>"\3\2",'ac'=>"\1",'cp'=>array('p')),
		'audio'=>array('c'=>"\117",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\110",'ac19'=>'not(@src)','ac22'=>'@src','t'=>1),
		'b'=>array('c'=>"\7",'ac'=>"\4"),
		'bdi'=>array('c'=>"\7",'ac'=>"\4"),
		'bdo'=>array('c'=>"\7",'ac'=>"\4"),
		'blockquote'=>array('c'=>"\43",'ac'=>"\1",'cp'=>array('p')),
		'br'=>array('c'=>"\5",'nt'=>1),
		'button'=>array('c'=>"\17",'ac'=>"\4",'dd'=>"\10"),
		'canvas'=>array('c'=>"\107",'ac'=>"\0",'t'=>1),
		'caption'=>array('c'=>"\200",'ac'=>"\1",'dd'=>"\0\0\200"),
		'cite'=>array('c'=>"\7",'ac'=>"\4"),
		'code'=>array('c'=>"\7",'ac'=>"\4"),
		'col'=>array('c'=>"\0\0\0\100",'nt'=>1),
		'colgroup'=>array('c'=>"\200",'ac'=>"\0\0\0\100",'ac30'=>'not(@span)','nt'=>1),
		'datalist'=>array('c'=>"\5",'ac'=>"\4\0\20"),
		'dd'=>array('c'=>"\0\0\1",'ac'=>"\1",'cp'=>array('dd','dt')),
		'del'=>array('c'=>"\5",'ac'=>"\0",'t'=>1),
		'details'=>array('c'=>"\53",'ac'=>"\1\0\4"),
		'dfn'=>array('c'=>"\7\0\0\40",'ac'=>"\4",'dd'=>"\0\0\0\40"),
		'dialog'=>array('c'=>"\41",'ac'=>"\1"),
		'div'=>array('c'=>"\3",'ac'=>"\1",'cp'=>array('p')),
		'dl'=>array('c'=>"\3",'ac'=>"\0\0\1",'nt'=>1,'cp'=>array('p')),
		'dt'=>array('c'=>"\0\0\1",'ac'=>"\1",'dd'=>"\20\42",'cp'=>array('dd','dt')),
		'em'=>array('c'=>"\7",'ac'=>"\4"),
		'embed'=>array('c'=>"\117",'nt'=>1),
		'fieldset'=>array('c'=>"\43",'ac'=>"\1\0\40",'cp'=>array('p')),
		'figcaption'=>array('c'=>"\0\0\0\0\2",'ac'=>"\1"),
		'figure'=>array('c'=>"\43",'ac'=>"\1\0\0\0\2"),
		'footer'=>array('c'=>"\3\44",'ac'=>"\1",'dd'=>"\0\40",'cp'=>array('p')),
		'form'=>array('c'=>"\3\0\0\20",'ac'=>"\1",'dd'=>"\0\0\0\20",'cp'=>array('p')),
		'h1'=>array('c'=>"\23\1",'ac'=>"\4",'cp'=>array('p')),
		'h2'=>array('c'=>"\23\1",'ac'=>"\4",'cp'=>array('p')),
		'h3'=>array('c'=>"\23\1",'ac'=>"\4",'cp'=>array('p')),
		'h4'=>array('c'=>"\23\1",'ac'=>"\4",'cp'=>array('p')),
		'h5'=>array('c'=>"\23\1",'ac'=>"\4",'cp'=>array('p')),
		'h6'=>array('c'=>"\23\1",'ac'=>"\4",'cp'=>array('p')),
		'header'=>array('c'=>"\3\44",'ac'=>"\1",'dd'=>"\0\40",'cp'=>array('p')),
		'hgroup'=>array('c'=>"\23",'ac'=>"\0\1",'nt'=>1,'cp'=>array('p')),
		'hr'=>array('c'=>"\1",'nt'=>1,'cp'=>array('p')),
		'i'=>array('c'=>"\7",'ac'=>"\4"),
		'img'=>array('c'=>"\117",'c3'=>'@usemap','nt'=>1),
		'input'=>array('c'=>"\17",'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"','nt'=>1),
		'ins'=>array('c'=>"\7",'ac'=>"\0",'t'=>1),
		'kbd'=>array('c'=>"\7",'ac'=>"\4"),
		'keygen'=>array('c'=>"\17",'nt'=>1),
		'label'=>array('c'=>"\17\0\0\4",'ac'=>"\4",'dd'=>"\0\0\0\4"),
		'legend'=>array('c'=>"\0\0\40",'ac'=>"\4"),
		'li'=>array('c'=>"\0\0\0\0\1",'ac'=>"\1",'cp'=>array('li')),
		'map'=>array('c'=>"\7",'ac'=>"\0",'t'=>1),
		'mark'=>array('c'=>"\7",'ac'=>"\4"),
		'menu'=>array('c'=>"\13",'c3'=>'@type="toolbar"','c1'=>'@type="toolbar" or @type="list"','ac'=>"\1\0\0\0\1",'cp'=>array('p')),
		'meter'=>array('c'=>"\7\10\0\2",'ac'=>"\4",'dd'=>"\0\0\0\2"),
		'nav'=>array('c'=>"\3\2",'ac'=>"\1",'cp'=>array('p')),
		'object'=>array('c'=>"\117",'c3'=>'@usemap','ac'=>"\11\0\0\1"),
		'ol'=>array('c'=>"\3",'ac'=>"\0\0\0\0\1",'nt'=>1,'cp'=>array('p')),
		'optgroup'=>array('c'=>"\0\20",'ac'=>"\0\0\20",'nt'=>1,'cp'=>array('optgroup','option')),
		'option'=>array('c'=>"\0\20\20",'cp'=>array('option')),
		'output'=>array('c'=>"\7",'ac'=>"\4"),
		'p'=>array('c'=>"\3",'ac'=>"\4",'cp'=>array('p')),
		'param'=>array('c'=>"\0\0\0\1",'nt'=>1),
		'pre'=>array('c'=>"\3",'ac'=>"\4",'cp'=>array('p')),
		'progress'=>array('c'=>"\7\10\2",'ac'=>"\4",'dd'=>"\0\0\2"),
		'q'=>array('c'=>"\7",'ac'=>"\4"),
		'rp'=>array('c'=>"\0\200",'ac'=>"\4",'cp'=>array('rp','rt')),
		'rt'=>array('c'=>"\0\200",'ac'=>"\4",'cp'=>array('rp','rt')),
		'ruby'=>array('c'=>"\7\0\0\10",'ac'=>"\4\200",'dd'=>"\0\0\0\10"),
		's'=>array('c'=>"\7",'ac'=>"\4"),
		'samp'=>array('c'=>"\7",'ac'=>"\4"),
		'section'=>array('c'=>"\3\2",'ac'=>"\1",'cp'=>array('p')),
		'select'=>array('c'=>"\17",'ac'=>"\0\20",'nt'=>1),
		'small'=>array('c'=>"\7",'ac'=>"\4"),
		'source'=>array('c'=>"\0\0\10",'nt'=>1),
		'span'=>array('c'=>"\7",'ac'=>"\4"),
		'strong'=>array('c'=>"\7",'ac'=>"\4"),
		'sub'=>array('c'=>"\7",'ac'=>"\4"),
		'summary'=>array('c'=>"\0\0\4",'ac'=>"\4"),
		'sup'=>array('c'=>"\7",'ac'=>"\4"),
		'table'=>array('c'=>"\3\0\200",'ac'=>"\200",'nt'=>1,'cp'=>array('p')),
		'tbody'=>array('c'=>"\200",'ac'=>"\0\0\0\200",'nt'=>1,'cp'=>array('tbody','tfoot','thead')),
		'td'=>array('c'=>"\40\100",'ac'=>"\1",'cp'=>array('td','th')),
		'textarea'=>array('c'=>"\17"),
		'tfoot'=>array('c'=>"\200",'ac'=>"\0\0\0\200",'nt'=>1,'cp'=>array('tbody','thead')),
		'th'=>array('c'=>"\0\100",'ac'=>"\1",'dd'=>"\20\42",'cp'=>array('td','th')),
		'thead'=>array('c'=>"\200",'ac'=>"\0\0\0\200",'nt'=>1),
		'time'=>array('c'=>"\7",'ac'=>"\4"),
		'tr'=>array('c'=>"\200\0\0\200",'ac'=>"\0\100",'nt'=>1,'cp'=>array('tr')),
		'track'=>array('c'=>"\0\0\100",'nt'=>1),
		'u'=>array('c'=>"\7",'ac'=>"\4"),
		'ul'=>array('c'=>"\3",'ac'=>"\0\0\0\0\1",'nt'=>1,'cp'=>array('p')),
		'var'=>array('c'=>"\7",'ac'=>"\4"),
		'video'=>array('c'=>"\117",'c3'=>'@controls','ac'=>"\0\0\110",'ac19'=>'not(@src)','ac22'=>'@src','t'=>1),
		'wbr'=>array('c'=>"\5",'nt'=>1)
	);

	/**
	* Get the bitfield value for a given element name in a given context
	*
	* @param  string           $elName Name of the HTML element
	* @param  string           $k      Bitfield name: either 'c', 'ac' or 'dd'
	* @param  SimpleXMLElement $node   Context node (not necessarily the same as $elName)
	* @return string
	*/
	protected static function getBitfield($elName, $k, SimpleXMLElement $node)
	{
		if (!isset(self::$htmlElements[$elName][$k]))
		{
			return "\0";
		}

		$bitfield = self::$htmlElements[$elName][$k];

		foreach (str_split($bitfield, 1) as $byteNumber => $char)
		{
			foreach (str_split(strrev(decbin(ord($char))), 1) as $bitNumber => $v)
			{
				if (!$v)
				{
					// The bit is not set
					continue;
				}

				$n = $byteNumber * 8 + $bitNumber;

				// Test for an XPath condition for that category
				if (isset(self::$htmlElements[$elName][$k . $n]))
				{
					$xpath = self::$htmlElements[$elName][$k . $n];

					// We need DOMXPath to correctly evaluate the absence of an attribute
					$domNode  = dom_import_simplexml($node);
					$domXPath = new DOMXPath($domNode->ownerDocument);

					// If the XPath condition is not() fulfilled...
					if ($domXPath->evaluate('not(' . $xpath . ')', $domNode))
					{
						// ...turn off the corresponding bit
						$bitfield[$byteNumber] = $char ^ chr(1 << $bitNumber);
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
	protected function match($bitfield1, $bitfield2)
	{
		return (trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}