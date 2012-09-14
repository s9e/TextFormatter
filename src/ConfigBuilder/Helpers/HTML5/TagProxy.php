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
* Not really a tag proxy but naming things is hard. This class helps the RulesGenerator by answering
* questions such as "can this tag be a child/descendant of that other tag?" and others related to
* the HTML5 content model.
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
* @see  /scripts/patchTagProxy.php
*/
class TagProxy
{
	/**
	* @var integer allowChild bitfield (all branches)
	*/
	protected $allowChildBitfield = 0;

	/**
	* @var integer OR-ed bitfield representing all of the categories used by this tag's templates
	*/
	protected $contentBitfield = 0;

	/**
	* @var integer denyDescendant bitfield
	*/
	protected $denyDescendantBitfield = 0;

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
	* 
	*
	* @return void
	*/
	public function __construct($templates, array $options = array())
	{
		$this->loadTemplates($templates, $options);

		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}

	/**
	* 
	*
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
			if (!($rootBitfield & $this->allowChildBitfield))
			{
				return false;
			}
		}

		return true;
	}

	/**
	* 
	*
	* @return bool
	*/
	public function allowsDescendant(self $descendant)
	{
		return !($descendant->contentBitfield & $this->denyDescendantBitfield);
	}

	/**
	* 
	*
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
	* 
	*
	* @return bool
	*/
	public function isTransparent()
	{
		return $this->isTransparent;
	}

	/**
	* 
	*
	* @return void
	*/
	protected function loadTemplates($templates, array $options)
	{
		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">';

		foreach ($templates as $template)
		{
			$xsl .= $template;
		}

		if (isset($options['renderer'])
		 && !count($templates))
		{
			// Use the renderer
		}

		$xsl .= '</xsl:template>';

		$this->node = simplexml_load_string($xsl);;
	}

	/**
	* 
	*
	* @return integer
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
	* 
	*
	* @return array
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
	}

	/**
	* 
	*
	* @return array
	*/
	protected function analyseBranches()
	{
		/**
		* @var array allowChild bitfield for each branch
		*/
		$branchBitfields = array();

		foreach ($this->node->xpath('//xsl:apply-templates') as $at)
		{
			// We retrieve all non-XSL elements that
			$nodes = $at->xpath('ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]');

			if (empty($nodes))
			{
				// That tag might have an empty template for some reason, in which case there's
				// nothing to do here
				continue;
			}

			/**
			* @var integer allowChild bitfield for current branch. Starts with the value associated
			*              with <span> in order to approximate a value if the whole branch uses the
			*              transparent content model
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
					$branchBitfield = 0;

					// Also, it means that the tag itself isn't transparent
					$isTransparent = false;
				}

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= self::getBitfield($nodeName, 'ac', $node);

				// denyDescendant rules are cumulative
				$this->denyDescendantBitfield |= self::getBitfield($nodeName, 'dd', $node);
			}

			$branchBitfields[] = $branchBitfield;

			// Save the name of the last node processed. Its actual name, not the "span" workaround
			$this->leafNodes[] = $node->getName();
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
	* @see /scripts/patchTagProxy.php
	*/
	protected static $htmlElements = array(
		'a'=>array('c'=>15,'ac'=>0,'dd'=>8,'t'=>1),
		'abbr'=>array('c'=>7,'ac'=>4),
		'address'=>array('c'=>1027,'ac'=>1,'dd'=>1552,'cp'=>array('p')),
		'area'=>array('c'=>5),
		'article'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'aside'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'audio'=>array('c'=>79,'c3'=>'@controls','c1'=>'@controls','ac'=>4718592,'ac19'=>'not(@src)','ac22'=>'@src','t'=>1),
		'b'=>array('c'=>7,'ac'=>4),
		'bdi'=>array('c'=>7,'ac'=>4),
		'bdo'=>array('c'=>7,'ac'=>4),
		'blockquote'=>array('c'=>35,'ac'=>1,'cp'=>array('p')),
		'br'=>array('c'=>5),
		'button'=>array('c'=>15,'ac'=>4,'dd'=>8),
		'canvas'=>array('c'=>71,'ac'=>0,'t'=>1),
		'caption'=>array('c'=>128,'ac'=>1,'dd'=>8388608),
		'cite'=>array('c'=>7,'ac'=>4),
		'code'=>array('c'=>7,'ac'=>4),
		'col'=>array('c'=>536870912),
		'colgroup'=>array('c'=>128,'ac'=>536870912,'ac29'=>'not(@span)'),
		'datalist'=>array('c'=>5,'ac'=>1048580),
		'dd'=>array('c'=>65536,'ac'=>1,'cp'=>array('dd','dt')),
		'del'=>array('c'=>5,'ac'=>0,'t'=>1),
		'details'=>array('c'=>43,'ac'=>262145),
		'dfn'=>array('c'=>268435463,'ac'=>4,'dd'=>268435456),
		'dialog'=>array('c'=>33,'ac'=>1),
		'div'=>array('c'=>3,'ac'=>1,'cp'=>array('p')),
		'dl'=>array('c'=>3,'ac'=>65536,'cp'=>array('p')),
		'dt'=>array('c'=>65536,'ac'=>1,'dd'=>8720,'cp'=>array('dd','dt')),
		'em'=>array('c'=>7,'ac'=>4),
		'embed'=>array('c'=>79),
		'fieldset'=>array('c'=>35,'ac'=>2097153,'cp'=>array('p')),
		'figcaption'=>array('c'=>0x100000000,'ac'=>1),
		'figure'=>array('c'=>35,'ac'=>0x100000001),
		'footer'=>array('c'=>9219,'ac'=>1,'dd'=>8192,'cp'=>array('p')),
		'form'=>array('c'=>134217731,'ac'=>1,'dd'=>134217728,'cp'=>array('p')),
		'h1'=>array('c'=>275,'ac'=>4,'cp'=>array('p')),
		'h2'=>array('c'=>275,'ac'=>4,'cp'=>array('p')),
		'h3'=>array('c'=>275,'ac'=>4,'cp'=>array('p')),
		'h4'=>array('c'=>275,'ac'=>4,'cp'=>array('p')),
		'h5'=>array('c'=>275,'ac'=>4,'cp'=>array('p')),
		'h6'=>array('c'=>275,'ac'=>4,'cp'=>array('p')),
		'header'=>array('c'=>9219,'ac'=>1,'dd'=>8192,'cp'=>array('p')),
		'hgroup'=>array('c'=>19,'ac'=>256,'cp'=>array('p')),
		'hr'=>array('c'=>1,'cp'=>array('p')),
		'i'=>array('c'=>7,'ac'=>4),
		'img'=>array('c'=>79,'c3'=>'@usemap'),
		'input'=>array('c'=>15,'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"'),
		'ins'=>array('c'=>7,'ac'=>0,'t'=>1),
		'kbd'=>array('c'=>7,'ac'=>4),
		'keygen'=>array('c'=>15),
		'label'=>array('c'=>67108879,'ac'=>4,'dd'=>67108864),
		'legend'=>array('c'=>2097152,'ac'=>4),
		'li'=>array('c'=>0x80000000,'ac'=>1,'cp'=>array('li')),
		'map'=>array('c'=>7,'ac'=>0,'t'=>1),
		'mark'=>array('c'=>7,'ac'=>4),
		'menu'=>array('c'=>11,'c3'=>'@type="toolbar"','c1'=>'@type="toolbar" or @type="list"','ac'=>0x80000001,'cp'=>array('p')),
		'meter'=>array('c'=>33556487,'ac'=>4,'dd'=>33554432),
		'nav'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'object'=>array('c'=>79,'c3'=>'@usemap','ac'=>16777225),
		'ol'=>array('c'=>3,'ac'=>0x80000000,'cp'=>array('p')),
		'optgroup'=>array('c'=>4096,'ac'=>1048576,'cp'=>array('optgroup','option')),
		'option'=>array('c'=>1052672,'cp'=>array('option')),
		'output'=>array('c'=>7,'ac'=>4),
		'p'=>array('c'=>3,'ac'=>4,'cp'=>array('p')),
		'param'=>array('c'=>16777216),
		'pre'=>array('c'=>3,'ac'=>4,'cp'=>array('p')),
		'progress'=>array('c'=>133127,'ac'=>4,'dd'=>131072),
		'q'=>array('c'=>7,'ac'=>4),
		'rp'=>array('c'=>32768,'ac'=>4,'cp'=>array('rp','rt')),
		'rt'=>array('c'=>32768,'ac'=>4,'cp'=>array('rp','rt')),
		'ruby'=>array('c'=>7,'ac'=>32772),
		's'=>array('c'=>7,'ac'=>4),
		'samp'=>array('c'=>7,'ac'=>4),
		'section'=>array('c'=>515,'ac'=>1,'cp'=>array('p')),
		'select'=>array('c'=>15,'ac'=>4096),
		'small'=>array('c'=>7,'ac'=>4),
		'source'=>array('c'=>524288),
		'span'=>array('c'=>7,'ac'=>4),
		'strong'=>array('c'=>7,'ac'=>4),
		'sub'=>array('c'=>7,'ac'=>4),
		'summary'=>array('c'=>262144,'ac'=>4),
		'sup'=>array('c'=>7,'ac'=>4),
		'table'=>array('c'=>8388611,'ac'=>128,'cp'=>array('p')),
		'tbody'=>array('c'=>128,'ac'=>1073741824,'cp'=>array('tbody','tfoot','thead')),
		'td'=>array('c'=>16416,'ac'=>1,'cp'=>array('td','th')),
		'textarea'=>array('c'=>15),
		'tfoot'=>array('c'=>128,'ac'=>1073741824,'cp'=>array('tbody','thead')),
		'th'=>array('c'=>16384,'ac'=>1,'dd'=>8720,'cp'=>array('td','th')),
		'thead'=>array('c'=>128,'ac'=>1073741824),
		'time'=>array('c'=>7,'ac'=>4),
		'tr'=>array('c'=>1073741952,'ac'=>16384,'cp'=>array('tr')),
		'track'=>array('c'=>4194304),
		'u'=>array('c'=>7,'ac'=>4),
		'ul'=>array('c'=>3,'ac'=>0x80000000,'cp'=>array('p')),
		'var'=>array('c'=>7,'ac'=>4),
		'video'=>array('c'=>79,'c3'=>'@controls','ac'=>4718592,'ac19'=>'not(@src)','ac22'=>'@src','t'=>1),
		'wbr'=>array('c'=>5)
	);

	/**
	* Get the bitfield value for a given element name in a given context
	*
	* NOTE: will fail on 32-bit PHP for categories >= 0x100000000
	*
	* @param  string           $elName Name of the HTML element
	* @param  string           $k      Bitfield name: either 'c', 'ac' or 'dd'
	* @param  SimpleXMLElement $node   Context node (not necessarily the same as $elName)
	* @return integer
	*/
	protected static function getBitfield($elName, $k, SimpleXMLElement $node)
	{
		if (empty(self::$htmlElements[$elName][$k]))
		{
			return 0;
		}

		$bitfield = self::$htmlElements[$elName][$k];

		foreach (str_split(strrev(decbin($bitfield)), 1) as $n => $v)
		{
			if (!$v)
			{
				// The bit is not set
				continue;
			}

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
					$bitfield ^= 1 << $n;
				}
			}
		}

		return $bitfield;
	}
}