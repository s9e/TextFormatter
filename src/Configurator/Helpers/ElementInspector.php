<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMElement;
use DOMXPath;

class ElementInspector
{
	/**
	* This is an abridged version of the HTML5 content models and rules, with some liberties taken.
	*
	* For each element, up to three bitfields are defined: "c", "ac" and "dd". Bitfields are stored
	* as raw bytes, formatted using the octal notation to keep the sources ASCII.
	*
	*    "c" represents the categories the element belongs to. The categories are comprised of HTML5
	*        content models (such as "phrasing content" or "interactive content") plus a few special
	*        categories created to cover the parts of the specs that refer to "a group of X and Y
	*        elements" rather than a specific content model.
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
	*    "t" indicates that the element uses the "transparent" content model.
	*    "e" indicates that the element uses the "empty" content model.
	*    "v" indicates that the element is a void element.
	*   "nt" indicates that the element does not accept text nodes. (no text)
	*   "to" indicates that the element should only contain text. (text-only)
	*   "fe" indicates that the element is a formatting element. It will automatically be reopened
	*        when closed by an end tag of a different name.
	*    "b" indicates that the element is not phrasing content, which makes it likely to act like
	*        a block element.
	*
	* Finally, HTML5 defines "optional end tag" rules, where one element automatically closes its
	* predecessor. Those are used to generate closeParent rules and are stored in the "cp" key.
	*
	* @var array
	* @see /scripts/patchElementInspector.php
	*/
	protected static $htmlElements = [
		'a'=>['c'=>"\17\0\0\0\0\2",'c3'=>'@href','ac'=>"\0",'dd'=>"\10\0\0\0\0\2",'t'=>1,'fe'=>1],
		'abbr'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'address'=>['c'=>"\3\10",'ac'=>"\1",'dd'=>"\200\14",'b'=>1,'cp'=>['p']],
		'article'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['p']],
		'aside'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['p']],
		'audio'=>['c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\0\40\1",'ac29'=>'not(@src)','dd'=>"\0\0\0\0\0\4",'dd42'=>'@src','t'=>1],
		'b'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'base'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'bdi'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'bdo'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'blockquote'=>['c'=>"\103",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'body'=>['c'=>"\100\0\40",'ac'=>"\1",'dd'=>"\0",'b'=>1],
		'br'=>['c'=>"\5",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'button'=>['c'=>"\17\2",'ac'=>"\4",'dd'=>"\10"],
		'canvas'=>['c'=>"\47",'ac'=>"\0",'dd'=>"\0",'t'=>1],
		'caption'=>['c'=>"\0\1",'ac'=>"\1",'dd'=>"\0\0\20",'b'=>1],
		'cite'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'code'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'col'=>['c'=>"\0\0\200",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'colgroup'=>['c'=>"\0\1",'ac'=>"\0\0\200",'ac23'=>'not(@span)','dd'=>"\0",'nt'=>1,'e'=>1,'e?'=>'@span','b'=>1],
		'data'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'datalist'=>['c'=>"\5",'ac'=>"\4\0\1\100",'dd'=>"\0"],
		'dd'=>['c'=>"\0\200\0\4",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['dd','dt']],
		'del'=>['c'=>"\5",'ac'=>"\0",'dd'=>"\0",'t'=>1],
		'details'=>['c'=>"\113",'ac'=>"\1\0\0\20",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'dfn'=>['c'=>"\7\0\0\0\100",'ac'=>"\4",'dd'=>"\0\0\0\0\100"],
		'dialog'=>['c'=>"\101",'ac'=>"\1",'dd'=>"\0",'b'=>1],
		'div'=>['c'=>"\3\200",'ac'=>"\1\0\1\4",'ac0'=>'not(ancestor::dl)','dd'=>"\0",'b'=>1,'cp'=>['p']],
		'dl'=>['c'=>"\3",'c1'=>'dt and dd','ac'=>"\0\200\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'dt'=>['c'=>"\0\200\0\4",'ac'=>"\1",'dd'=>"\200\4\10",'b'=>1,'cp'=>['dd','dt']],
		'em'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'embed'=>['c'=>"\57",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'fieldset'=>['c'=>"\103\2",'ac'=>"\1\0\0\200",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'figcaption'=>['c'=>"\0\0\0\0\0\10",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'figure'=>['c'=>"\103",'ac'=>"\1\0\0\0\0\10",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'footer'=>['c'=>"\3\110\10",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['p']],
		'form'=>['c'=>"\3\0\0\0\40",'ac'=>"\1",'dd'=>"\0\0\0\0\40",'b'=>1,'cp'=>['p']],
		'h1'=>['c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h2'=>['c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h3'=>['c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h4'=>['c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h5'=>['c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'h6'=>['c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'head'=>['c'=>"\0\0\40",'ac'=>"\20",'dd'=>"\0",'nt'=>1,'b'=>1],
		'header'=>['c'=>"\3\110\10",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['p']],
		'hr'=>['c'=>"\1",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>['p']],
		'html'=>['c'=>"\0",'ac'=>"\0\0\40",'dd'=>"\0",'nt'=>1,'b'=>1],
		'i'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'iframe'=>['c'=>"\57",'ac'=>"\4",'dd'=>"\0"],
		'img'=>['c'=>"\57\40\100",'c3'=>'@usemap','ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'input'=>['c'=>"\17\40",'c3'=>'@type!="hidden"','c13'=>'@type!="hidden" or @type="hidden"','c1'=>'@type!="hidden"','ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'ins'=>['c'=>"\7",'ac'=>"\0",'dd'=>"\0",'t'=>1],
		'kbd'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'label'=>['c'=>"\17\40\0\0\10",'ac'=>"\4",'dd'=>"\0\0\2\0\10"],
		'legend'=>['c'=>"\0\0\0\200",'ac'=>"\204",'dd'=>"\0",'b'=>1],
		'li'=>['c'=>"\0\0\0\0\0\1",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['li']],
		'link'=>['c'=>"\25",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1],
		'main'=>['c'=>"\3\110\20\0\20",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'mark'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'media element'=>['c'=>"\0\0\0\0\0\4",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'b'=>1],
		'meta'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meter'=>['c'=>"\7\0\2\0\4",'ac'=>"\4",'dd'=>"\0\0\0\0\4"],
		'nav'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['p']],
		'noscript'=>['c'=>"\25",'ac'=>"\0",'dd'=>"\0",'nt'=>1],
		'object'=>['c'=>"\47",'ac'=>"\0\0\0\0\2",'dd'=>"\0",'t'=>1],
		'ol'=>['c'=>"\3",'c1'=>'li','ac'=>"\0\0\1\0\0\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'optgroup'=>['c'=>"\0\0\4",'ac'=>"\0\0\1\100",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['optgroup','option']],
		'option'=>['c'=>"\0\0\4\100",'ac'=>"\0",'dd'=>"\0",'b'=>1,'cp'=>['option']],
		'output'=>['c'=>"\7\2",'ac'=>"\4",'dd'=>"\0"],
		'p'=>['c'=>"\3",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'param'=>['c'=>"\0\0\0\0\2",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'picture'=>['c'=>"\45",'ac'=>"\0\0\101",'dd'=>"\0",'nt'=>1],
		'pre'=>['c'=>"\3",'ac'=>"\4",'dd'=>"\0",'pre'=>1,'b'=>1,'cp'=>['p']],
		'progress'=>['c'=>"\7\0\2\10",'ac'=>"\4",'dd'=>"\0\0\0\10"],
		'q'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'rb'=>['c'=>"\0\20",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['rb','rt','rtc']],
		'rp'=>['c'=>"\0\20\0\2",'ac'=>"\4",'dd'=>"\0",'b'=>1],
		'rt'=>['c'=>"\0\20\0\2",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>['rb','rt']],
		'rtc'=>['c'=>"\0\20",'ac'=>"\4\0\0\2",'dd'=>"\0",'b'=>1,'cp'=>['rt','rtc']],
		'ruby'=>['c'=>"\7",'ac'=>"\4\20",'dd'=>"\0"],
		's'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'samp'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'script'=>['c'=>"\25\0\1",'ac'=>"\0",'dd'=>"\0",'to'=>1],
		'section'=>['c'=>"\3\4",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>['p']],
		'select'=>['c'=>"\17\2",'ac'=>"\0\0\5",'dd'=>"\0",'nt'=>1],
		'slot'=>['c'=>"\5",'ac'=>"\0",'dd'=>"\0",'t'=>1],
		'small'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'source'=>['c'=>"\0\0\100\40",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'span'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'strong'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'style'=>['c'=>"\21",'ac'=>"\0",'dd'=>"\0",'to'=>1,'b'=>1],
		'sub'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'summary'=>['c'=>"\0\0\0\20",'ac'=>"\204",'dd'=>"\0",'b'=>1],
		'sup'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'table'=>['c'=>"\3\0\20",'ac'=>"\0\1\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'tbody'=>['c'=>"\0\1",'ac'=>"\0\0\1\0\200",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['tbody','td','th','thead','tr']],
		'td'=>['c'=>"\100\0\0\1",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>['td','th']],
		'template'=>['c'=>"\25\0\201",'ac'=>"\0",'dd'=>"\0",'nt'=>1],
		'textarea'=>['c'=>"\17\2",'ac'=>"\0",'dd'=>"\0",'pre'=>1,'to'=>1],
		'tfoot'=>['c'=>"\0\1",'ac'=>"\0\0\1\0\200",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['tbody','td','th','thead','tr']],
		'th'=>['c'=>"\0\0\0\1",'ac'=>"\1",'dd'=>"\200\104",'b'=>1,'cp'=>['td','th']],
		'thead'=>['c'=>"\0\1",'ac'=>"\0\0\1\0\200",'dd'=>"\0",'nt'=>1,'b'=>1],
		'time'=>['c'=>"\7",'ac'=>"\4",'ac2'=>'@datetime','dd'=>"\0"],
		'title'=>['c'=>"\20",'ac'=>"\0",'dd'=>"\0",'to'=>1,'b'=>1],
		'tr'=>['c'=>"\0\1\0\0\200",'ac'=>"\0\0\1\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['td','th','tr']],
		'track'=>['c'=>"\0\0\0\0\1",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'u'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1],
		'ul'=>['c'=>"\3",'c1'=>'li','ac'=>"\0\0\1\0\0\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>['p']],
		'var'=>['c'=>"\7",'ac'=>"\4",'dd'=>"\0"],
		'video'=>['c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\0\40\1",'ac29'=>'not(@src)','dd'=>"\0\0\0\0\0\4",'dd42'=>'@src','t'=>1],
		'wbr'=>['c'=>"\5",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1]
	];

	/**
	* Test whether given child element closes given parent element
	*
	* @param  DOMElement $child
	* @param  DOMElement $parent
	* @return bool
	*/
	public static function closesParent(DOMElement $child, DOMElement $parent)
	{
		$parentName = $parent->nodeName;
		$childName  = $child->nodeName;

		return !empty(self::$htmlElements[$childName]['cp']) && in_array($parentName, self::$htmlElements[$childName]['cp'], true);
	}

	/**
	* Test whether given element disallows text nodes
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function disallowsText(DOMElement $element)
	{
		return self::hasProperty($element, 'nt');
	}

	/**
	* Return the "allowChild" bitfield for given element
	*
	* @param  DOMElement $element
	* @return string
	*/
	public static function getAllowChildBitfield(DOMElement $element)
	{
		return self::getBitfield($element, 'ac');
	}

	/**
	* Return the "category" bitfield for given element
	*
	* @param  DOMElement $element
	* @return string
	*/
	public static function getCategoryBitfield(DOMElement $element)
	{
		return self::getBitfield($element, 'c');
	}

	/**
	* Return the "denyDescendant" bitfield for given element
	*
	* @param  DOMElement $element
	* @return string
	*/
	public static function getDenyDescendantBitfield(DOMElement $element)
	{
		return self::getBitfield($element, 'dd');
	}

	/**
	* Test whether given element is a block element
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function isBlock(DOMElement $element)
	{
		return self::hasProperty($element, 'b');
	}

	/**
	* Test whether given element uses the empty content model
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function isEmpty(DOMElement $element)
	{
		return self::hasProperty($element, 'e');
	}

	/**
	* Test whether given element is a formatting element
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function isFormattingElement(DOMElement $element)
	{
		return self::hasProperty($element, 'fe');
	}

	/**
	* Test whether given element only accepts text nodes
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function isTextOnly(DOMElement $element)
	{
		return self::hasProperty($element, 'to');
	}

	/**
	* Test whether given element uses the transparent content model
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function isTransparent(DOMElement $element)
	{
		return self::hasProperty($element, 't');
	}

	/**
	* Test whether given element uses the void content model
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function isVoid(DOMElement $element)
	{
		return self::hasProperty($element, 'v');
	}

	/**
	* Test whether given element preserves whitespace in its content
	*
	* @param  DOMElement $element
	* @return bool
	*/
	public static function preservesWhitespace(DOMElement $element)
	{
		return self::hasProperty($element, 'pre');
	}

	/**
	* Evaluate an XPath query using given element as context node
	*
	* @param  string     $query   XPath query
	* @param  DOMElement $element Context node
	* @return bool
	*/
	protected static function evaluate($query, DOMElement $element)
	{
		$xpath = new DOMXPath($element->ownerDocument);

		return $xpath->evaluate('boolean(' . $query . ')', $element);
	}

	/**
	* Get the bitfield value for a given element
	*
	* @param  DOMElement $element Context node
	* @param  string     $name    Bitfield name: either 'c', 'ac' or 'dd'
	* @return string
	*/
	protected static function getBitfield(DOMElement $element, $name)
	{
		$props    = self::getProperties($element);
		$bitfield = self::toBin($props[$name]);

		// For each bit set to 1, test whether there is an XPath condition to it and whether it is
		// fulfilled. If not, turn the bit to 0
		foreach (array_keys(array_filter(str_split($bitfield, 1))) as $bitNumber)
		{
			$conditionName = $name . $bitNumber;
			if (isset($props[$conditionName]) && !self::evaluate($props[$conditionName], $element))
			{
				$bitfield[$bitNumber] = '0';
			}
		}

		return self::toRaw($bitfield);
	}

	/**
	* Return the properties associated with given element
	*
	* Returns span's properties if the element is not defined
	*
	* @param  DOMElement $element
	* @return array
	*/
	protected static function getProperties(DOMElement $element)
	{
		return (isset(self::$htmlElements[$element->nodeName])) ? self::$htmlElements[$element->nodeName] : self::$htmlElements['span'];
	}

	/**
	* Test whether given element has given property in context
	*
	* @param  DOMElement $element  Context node
	* @param  string     $propName Property name, see self::$htmlElements
	* @return bool
	*/
	protected static function hasProperty(DOMElement $element, $propName)
	{
		$props = self::getProperties($element);

		return !empty($props[$propName]) && (!isset($props[$propName . '?']) || self::evaluate($props[$propName . '?'], $element));
	}

	/**
	* Convert a raw string to a series of 0 and 1 in LSB order
	*
	* @param  string $raw
	* @return string
	*/
	protected static function toBin($raw)
	{
		$bin = '';
		foreach (str_split($raw, 1) as $char)
		{
			$bin .= strrev(substr('0000000' . decbin(ord($char)), -8));
		}

		return $bin;
	}

	/**
	* Convert a series of 0 and 1 in LSB order to a raw string
	*
	* @param  string $bin
	* @return string
	*/
	protected static function toRaw($bin)
	{
		return implode('', array_map('chr', array_map('bindec', array_map('strrev', str_split($bin, 8)))));
	}
}