<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Helpers;

use DOMDocument;
use DOMXPath;
use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\TagCollection;

abstract class HTML5Helper
{
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
	* @see /scripts/patchHTML5Helper.php
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
		'col'=>array('c'=>536870912,'c29'=>'not(@span)'),
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
		'source'=>array('c'=>524288,'c19'=>'not(@src)'),
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
		'track'=>array('c'=>4194304,'c22'=>'@src'),
		'u'=>array('c'=>7,'ac'=>4),
		'ul'=>array('c'=>3,'ac'=>0x80000000,'cp'=>array('p')),
		'var'=>array('c'=>7,'ac'=>4),
		'video'=>array('c'=>79,'c3'=>'@controls','ac'=>4718592,'ac19'=>'not(@src)','ac22'=>'@src','t'=>1),
		'wbr'=>array('c'=>5)
	);

	/**
	* Generate rules based on HTML5 content models
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
	* @see  /scripts/patchHTML5Helper.php
	*
	* Possible options:
	*
	*  renderer:    instance of Renderer, used to render tags that have no individual templates set
	*  rootElement: name of the HTML element used as the root of the rendered text
	*
	* @param  TagCollection $tags    Tags collection
	* @param  array         $options Array of option settings
	* @return array
	*/
	public static function getRules(TagCollection $tags, array $options = array())
	{
		$preHTML  = '<div>';
		$postHTML = '</div>';

		$namespaces = array();
		foreach ($tags as $tagName => $tag)
		{
			$pos = strpos($tagName, ':');

			if ($pos !== false)
			{
				$prefix = substr($tagName, 0, $pos);
				$namespaces[$prefix] = 'urn:s9e:TextFormatter:' . $prefix;
			}
		}

		$nsDeclarations = ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform"';
		foreach ($namespaces as $prefix => $uri)
		{
			$nsDeclarations .= ' xmlns:' . $prefix . '="' . $uri . '"';
		}

		foreach ($tags as $tagName => $tag)
		{
			$xsl = '';
			foreach ($tag->templates as $predicate => $template)
			{
				if ($predicate !== '')
				{
					$predicate = '[' . htmlspecialchars($predicate) . ']';
				}

				$xsl .= '<xsl:template match="' . $tagName . $predicate . '">'
				      . $preHTML
				      . $template
				      . $postHTML
				      . '</xsl:template>';
			}

			if ($xsl === '')
			{
				// Use the renderer
			}

			$dom = new DOMDocument;
			$dom->loadXML('<xsl:stylesheet' . $nsDeclarations . '>' . $xsl . '</xsl:stylesheet>');

		}









		if (isset($options['rootElement']))
		{
			if (!isset(self::htmlElements[$options['rootElement']]))
			{
				throw new InvalidArgumentException("Unknown HTML element '" . $options['rootElement'] . "'");
			}

			/**
			* Create a fake tag for our root element. "*fake-root*" is not a valid tag name so it
			* shouldn't conflict with any existing tag
			*/
			$rootTag = '*fake-root*';

			$tagsConfig[$rootTag]['xsl'] =
				'<xsl:template match="' . $rootTag . '">
					<' . $options['rootElement'] . '>
						<xsl:apply-templates />
					</' . $options['rootElement'] . '>
				</xsl:template>';
		}

		$tagsInfo = array();
		foreach ($tags as $tagName => $tag)
		{
			/**
			* If a tag has no templates set, we try to render it alone and use the result as its
			* pseudo-template
			*/
			if (!count($tag->templates)
			 && isset($options['renderer']))
			{
				$uid = uniqid('', true);

				/**
				* @todo should probably also include all of the tag's attributes
				*/
				$xml = '<rt' . $nsDeclarations . '>'
				     . '<' . $tagName . '>' . $uid . '</' . $tagName . '>'
				     . '</rt>';

				$tagConfig['xsl'] = '<xsl:template match="' . $tagName . '">'
				                  . str_replace($uid, '<xsl:apply-templates/>', $renderer->render($xml))
				                  . '</xsl:template>';
			}

			$tagInfo = array(
				'leafNode' => array()
			);

			$tagInfo['root'] = simplexml_load_string(
				'<xsl:stylesheet' . self::generateNamespaceDeclarations() . '>' . $tagConfig['xsl'] . '</xsl:stylesheet>'
			);

			/**
			* Get every non-XSL (and therefore, HTML) element with no non-XSL ancestor
			*/
			$tagInfo['rootNode'] = $tagInfo['root']->xpath('//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"][not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]');

			/**
			* Compute the category bitfield of every first element
			*/
			$tagInfo['firstChildrenCategoryBitfield'] = array();

			foreach ($tagInfo['rootNode'] as $rootNode)
			{
				$tagInfo['firstChildrenCategoryBitfield'][]
					= self::filterHTMLRulesBitfield($rootNode->getName(), 'c', $rootNode);
			}

			/**
			* Get every HTML element from this tag's template(s) and generate a bitfield that
			* represents all the content models in use
			*/
			$tagInfo['usedCategories'] = 0;

			foreach ($tagInfo['root']->xpath('//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]') as $node)
			{
				$tagInfo['usedCategories'] |= self::filterHTMLRulesBitfield($node->getName(), 'c', $node);
			}

			/**
			* For each <xsl:apply-templates/> element, iterate over all the HTML ancestors, compute
			* the allowChildBitfields and denyDescendantBitfield values, and save the last HTML
			* child of the branch
			*/
			$tagInfo['denyDescendantBitfield'] = 0;

			foreach ($tagInfo['root']->xpath('//xsl:apply-templates') as $at)
			{
				$allowChildBitfield = null;

				foreach ($at->xpath('ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]') as $node)
				{
					$elName = $node->getName();

					if (empty(self::htmlElements[$elName]['t']))
					{
						/**
						* If this element does not use the transparent content model, we discard its
						* parent's bitfield
						*/
						$allowChildBitfield = 0;

						$tagInfo['isTransparent'] = false;
					}
					elseif (!isset($allowChildBitfield))
					{
						/**
						* If this element uses the transparent content model and this is the first
						* HTML element of this template, we reuse its category bitfield. It's not
						* exactly how it should work though, as at this point we don't know what
						* category enabled this tag
						*/
						$allowChildBitfield
							= self::filterHTMLRulesBitfield($elName, 'c', $node);

						/**
						* Accumulate the denied descendants
						*/
						$tagInfo['denyDescendantBitfield'] |= self::filterHTMLRulesBitfield($elName, 'dd', $node);

						if (!isset($tagInfo['isTransparent']))
						{
							$tagInfo['isTransparent'] = true;
						}
					}

					$allowChildBitfield
						|= self::filterHTMLRulesBitfield($elName, 'ac', $node);
				}

				$tagInfo['allowChildBitfields'][] = $allowChildBitfield;
				$tagInfo['leafNode'][] = $node;
			}

			$tagsInfo[$tagName] = $tagInfo;
		}

		$tagsOptions = array();

		/**
		* Generate closeParent rules
		*/
		foreach ($tagsInfo as $tagName => $tagInfo)
		{
			if (!empty($tagInfo['isTransparent']))
			{
				$tagsOptions[$tagName]['isTransparent'] = true;
			}

			foreach ($tagInfo['rootNode'] as $rootNode)
			{
				$elName = $rootNode->getName();

				if (!isset(self::htmlElements[$elName]['cp']))
				{
					continue;
				}

				foreach ($tagsInfo as $targetName => $targetInfo)
				{
					foreach ($targetInfo['leafNode'] as $leafNode)
					{
						if (in_array($leafNode->getName(), self::htmlElements[$elName]['cp'], true))
						{
							$tagsOptions[$tagName]['rules']['closeParent'][] = $targetName;
						}
					}
				}
			}
		}

		/**
		* Generate allowChild/denyChild rules
		*/
		foreach ($tagsInfo as $tagName => $tagInfo)
		{
			/**
			* If this tag allows no children, we deny every one of them
			*/
			if (empty($tagInfo['allowChildBitfields']))
			{
				foreach ($tagsInfo as $targetName => $targetInfo)
				{
					$tagsOptions[$tagName]['rules']['denyChild'][] = $targetName;
				}

				continue;
			}

			foreach ($tagInfo['allowChildBitfields'] as $allowChildBitfield)
			{
				foreach ($tagsInfo as $targetName => $targetInfo)
				{
					foreach ($targetInfo['firstChildrenCategoryBitfield'] as $rootNodeBitfield)
					{
						$action = ($allowChildBitfield & $rootNodeBitfield)
								? 'allowChild'
								: 'denyChild';

						$tagsOptions[$tagName]['rules'][$action][] = $targetName;
					}
				}
			}
		}

		/**
		* Generate denyDescendant rules
		*/
		foreach ($tagsInfo as $tagName => $tagInfo)
		{
			foreach ($tagsInfo as $targetName => $targetInfo)
			{
				if ($tagInfo['denyDescendantBitfield'] & $targetInfo['usedCategories'])
				{
					$tagsOptions[$tagName]['rules']['denyDescendant'][] = $targetName;
				}
			}
		}

		/**
		* Sets the options related to the root element
		*
		* @todo sometimes, the name of the element may not be enough; the presence and content of
		*       some attributes may change the content model. Additionally, ancestors may have
		*       restrictions on their descendants
		*
		* @todo some XPath conditions may need to be expanded. For instance, "@foo" can be satisfied
		*       by "<xsl:attribute name='foo'>" or "<xsl:copy-of select='@foo'/>"
		*/
		if (isset($options['rootElement']))
		{
			/**
			* Tags that cannot be a child of our root tag gets the disallowAsRoot option
			*/
			if (isset($tagsOptions[$rootTag]['rules']['denyChild']))
			{
				foreach ($tagsOptions[$rootTag]['rules']['denyChild'] as $tagName)
				{
					$tagsOptions[$tagName]['disallowAsRoot'] = true;
				}
			}

			/**
			* Tags that cannot be a descendant of our root tag get the disable option
			*/
			if (isset($tagsOptions[$rootTag]['rules']['denyDescendant']))
			{
				foreach ($tagsOptions[$rootTag]['rules']['denyDescendant'] as $tagName)
				{
					$tagsOptions[$tagName]['disable'] = true;
				}
			}

			/**
			* Now remove any mention of our root tag from the return array
			*/
			unset($tagsOptions[$rootTag]);

			foreach ($tagsOptions as &$tagOptions)
			{
				if (isset($tagOptions['rules']))
				{
					foreach ($tagOptions['rules'] as $rule => $targets)
					{
						/**
						* First we flip the target so we can unset the fake tag by key, then we
						* flip them back, which rearranges their keys as a side-effect
						*/
						$targets = array_flip($targets);
						unset($targets[$rootTag]);
						$tagOptions['rules'][$rule] = array_flip($targets);
					}
				}
			}
			unset($tagOptions);
		}

		/**
		* Deduplicate rules and resolve conflicting rules
		*/
		$precedence = array(
			array('denyDescendant', 'denyChild'),
			array('denyDescendant', 'allowChild'),
			array('denyChild', 'allowChild')
		);

		foreach ($tagsOptions as $tagName => &$tagOptions)
		{
			// flip the rules targets
			$tagOptions['rules'] = array_map('array_flip', $tagOptions['rules']);

			// apply precedence, e.g. if there's a denyChild rule, remove any allowChild rules
			foreach ($precedence as $pair)
			{
				list($k1, $k2) = $pair;

				if (!isset($tagOptions['rules'][$k1], $tagOptions['rules'][$k2]))
				{
					continue;
				}

				$tagOptions['rules'][$k2] = array_diff_key(
					$tagOptions['rules'][$k2],
					$tagOptions['rules'][$k1]
				);
			}

			// flip the rules again
			$tagOptions['rules'] = array_map('array_keys', $tagOptions['rules']);

			// remove empty rules
			$tagOptions['rules'] = array_filter($tagOptions['rules']);
		}
		unset($tagOptions);

		return $tagsOptions;
	}

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
		if (empty(self::htmlElements[$elName][$k]))
		{
			return 0;
		}

		$bitfield = self::htmlElements[$elName][$k];

		foreach (str_split(strrev(decbin($bitfield)), 1) as $n => $v)
		{
			if (!$v)
			{
				// The bit is not set
				continue;
			}

			// Test for an XPath condition for that category
			if (isset(self::htmlElements[$elName][$k . $n])
			 && !$node->xpath(self::htmlElements[$elName][$k . $n]))
			{
				// The XPath query returned no results, therefore we turn off this bit
				$bitfield ^= 1 << $n;
			}
		}

		return $bitfield;
	}








	/**
	* 
	*
	* @return integer
	*/
	protected function getUsedBitfield()
	{
	}

	/**
	* 
	*
	* @return array
	*/
	protected function getRootBitfields(SimpleXMLElement $root)
	{
		// Get every non-XSL element with no non-XSL ancestor. This should return us the first
		// HTML element of every branch
		$nodes = $root->xpath('//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"][not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]');

		$bitfields = array();
		foreach ($nodes as $node)
		{
			$nodeName = $node->getName();

			if (!isset(self::$htmlElements[$nodeName]))
			{
				// Unknown elements are treated as if they were a <span> element
				$nodeName = 'span';
			}

			$bitfields[] = self::getBitfield($nodeName, 'c', $node);
		}

		return $bitfields;
	}

	/**
	* 
	*
	* @return array
	*/
	protected function getLeafBitfield(SimpleXMLElement $root)
	{
		/**
		* @var array allowChild bitfield for each branch
		*/
		$branchBitfields = array();

		/**
		* @var integer allowChild bitfield (all branches)
		*/
		$ac = 0;

		/**
		* @var integer denyDescendant bitfield
		*/
		$dd = 0;

		foreach ($root->xpath('//xsl:apply-template') as $at)
		{
			// We retrieve all non-XSL elements that
			$nodes = $node->xpath('ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]', $at);

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
				}

				// allowChild rules are cumulative if transparent, and reset above otherwise
				$branchBitfield |= self::getBitfield($nodeName, 'ac', $node);

				// denyDescendant rules are cumulative
				$dd |= self::getBitfield($nodeName, 'dd', $node);
			}

			$branchBitfields[] = $branchBitfield;
		}

		// Now we take the bitfield of each branch and reduce them to a single ANDed bitfield
		if (!empty($branchBitfields)
		{
			$ac = $branchBitfields[0];

			foreach ($branchBitfields as $branchBitfield)
			{
				$ac &= $branchBitfield;
			}
		}

		return array($ac, $dd);
	}
}