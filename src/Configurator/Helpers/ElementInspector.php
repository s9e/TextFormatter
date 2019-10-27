<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMElement;
use DOMXPath;
class ElementInspector
{
	protected static $htmlElements = array(
		'a'=>array('c'=>"\17\0\0\0\0\2",'c3'=>'@href','ac'=>"\0",'dd'=>"\10\0\0\0\0\2",'t'=>1,'fe'=>1),
		'abbr'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'address'=>array('c'=>"\3\10",'ac'=>"\1",'dd'=>"\200\14",'b'=>1,'cp'=>array('p')),
		'article'=>array('c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>array('p')),
		'aside'=>array('c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>array('p')),
		'audio'=>array('c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\0\40\1",'ac29'=>'not(@src)','dd'=>"\0\0\0\0\0\4",'dd42'=>'@src','t'=>1),
		'b'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'base'=>array('c'=>"\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'bdi'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'bdo'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'blockquote'=>array('c'=>"\103",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'body'=>array('c'=>"\100\0\40",'ac'=>"\1",'dd'=>"\0",'b'=>1),
		'br'=>array('c'=>"\5",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1),
		'button'=>array('c'=>"\17\2",'ac'=>"\4",'dd'=>"\10"),
		'canvas'=>array('c'=>"\47",'ac'=>"\0",'dd'=>"\0",'t'=>1),
		'caption'=>array('c'=>"\0\1",'ac'=>"\1",'dd'=>"\0\0\20",'b'=>1),
		'cite'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'code'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'col'=>array('c'=>"\0\0\200",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'colgroup'=>array('c'=>"\0\1",'ac'=>"\0\0\200",'ac23'=>'not(@span)','dd'=>"\0",'nt'=>1,'e'=>1,'e?'=>'@span','b'=>1),
		'data'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'datalist'=>array('c'=>"\5",'ac'=>"\4\0\1\100",'dd'=>"\0"),
		'dd'=>array('c'=>"\0\200\0\4",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>array('dd','dt')),
		'del'=>array('c'=>"\5",'ac'=>"\0",'dd'=>"\0",'t'=>1),
		'details'=>array('c'=>"\113",'ac'=>"\1\0\0\20",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'dfn'=>array('c'=>"\7\0\0\0\100",'ac'=>"\4",'dd'=>"\0\0\0\0\100"),
		'dialog'=>array('c'=>"\101",'ac'=>"\1",'dd'=>"\0",'b'=>1),
		'div'=>array('c'=>"\3\200",'ac'=>"\1\0\1\4",'ac0'=>'not(ancestor::dl)','dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'dl'=>array('c'=>"\3",'c1'=>'dt and dd','ac'=>"\0\200\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'dt'=>array('c'=>"\0\200\0\4",'ac'=>"\1",'dd'=>"\200\4\10",'b'=>1,'cp'=>array('dd','dt')),
		'em'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'embed'=>array('c'=>"\57",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1),
		'fieldset'=>array('c'=>"\103\2",'ac'=>"\1\0\0\200",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'figcaption'=>array('c'=>"\0\0\0\0\0\10",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'figure'=>array('c'=>"\103",'ac'=>"\1\0\0\0\0\10",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'footer'=>array('c'=>"\3\110\10",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>array('p')),
		'form'=>array('c'=>"\3\0\0\0\40",'ac'=>"\1",'dd'=>"\0\0\0\0\40",'b'=>1,'cp'=>array('p')),
		'h1'=>array('c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'h2'=>array('c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'h3'=>array('c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'h4'=>array('c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'h5'=>array('c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'h6'=>array('c'=>"\203",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'head'=>array('c'=>"\0\0\40",'ac'=>"\20",'dd'=>"\0",'nt'=>1,'b'=>1),
		'header'=>array('c'=>"\3\110\10",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>array('p')),
		'hr'=>array('c'=>"\1",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>array('p')),
		'html'=>array('c'=>"\0",'ac'=>"\0\0\40",'dd'=>"\0",'nt'=>1,'b'=>1),
		'i'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'iframe'=>array('c'=>"\57",'ac'=>"\4",'dd'=>"\0"),
		'img'=>array('c'=>"\57\40\100",'c3'=>'@usemap','ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1),
		'input'=>array('c'=>"\17\40",'c3'=>'@type!="hidden"','c13'=>'@type!="hidden" or @type="hidden"','c1'=>'@type!="hidden"','ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1),
		'ins'=>array('c'=>"\7",'ac'=>"\0",'dd'=>"\0",'t'=>1),
		'kbd'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'label'=>array('c'=>"\17\40\0\0\10",'ac'=>"\4",'dd'=>"\0\0\2\0\10"),
		'legend'=>array('c'=>"\0\0\0\200",'ac'=>"\204",'dd'=>"\0",'b'=>1),
		'li'=>array('c'=>"\0\0\0\0\0\1",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>array('li')),
		'link'=>array('c'=>"\25",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1),
		'main'=>array('c'=>"\3\110\20\0\20",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'mark'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'media element'=>array('c'=>"\0\0\0\0\0\4",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'b'=>1),
		'meta'=>array('c'=>"\20",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'meter'=>array('c'=>"\7\0\2\0\4",'ac'=>"\4",'dd'=>"\0\0\0\0\4"),
		'nav'=>array('c'=>"\3\4",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>array('p')),
		'noscript'=>array('c'=>"\25",'ac'=>"\0",'dd'=>"\0",'nt'=>1),
		'object'=>array('c'=>"\47",'ac'=>"\0\0\0\0\2",'dd'=>"\0",'t'=>1),
		'ol'=>array('c'=>"\3",'c1'=>'li','ac'=>"\0\0\1\0\0\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'optgroup'=>array('c'=>"\0\0\4",'ac'=>"\0\0\1\100",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('optgroup','option')),
		'option'=>array('c'=>"\0\0\4\100",'ac'=>"\0",'dd'=>"\0",'b'=>1,'cp'=>array('option')),
		'output'=>array('c'=>"\7\2",'ac'=>"\4",'dd'=>"\0"),
		'p'=>array('c'=>"\3",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'param'=>array('c'=>"\0\0\0\0\2",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'picture'=>array('c'=>"\45",'ac'=>"\0\0\101",'dd'=>"\0",'nt'=>1),
		'pre'=>array('c'=>"\3",'ac'=>"\4",'dd'=>"\0",'pre'=>1,'b'=>1,'cp'=>array('p')),
		'progress'=>array('c'=>"\7\0\2\10",'ac'=>"\4",'dd'=>"\0\0\0\10"),
		'q'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'rb'=>array('c'=>"\0\20",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('rb','rt','rtc')),
		'rp'=>array('c'=>"\0\20\0\2",'ac'=>"\4",'dd'=>"\0",'b'=>1),
		'rt'=>array('c'=>"\0\20\0\2",'ac'=>"\4",'dd'=>"\0",'b'=>1,'cp'=>array('rb','rt')),
		'rtc'=>array('c'=>"\0\20",'ac'=>"\4\0\0\2",'dd'=>"\0",'b'=>1,'cp'=>array('rt','rtc')),
		'ruby'=>array('c'=>"\7",'ac'=>"\4\20",'dd'=>"\0"),
		's'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'samp'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'script'=>array('c'=>"\25\0\1",'ac'=>"\0",'dd'=>"\0",'to'=>1),
		'section'=>array('c'=>"\3\4",'ac'=>"\1",'dd'=>"\0",'b'=>1,'cp'=>array('p')),
		'select'=>array('c'=>"\17\2",'ac'=>"\0\0\5",'dd'=>"\0",'nt'=>1),
		'slot'=>array('c'=>"\5",'ac'=>"\0",'dd'=>"\0",'t'=>1),
		'small'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'source'=>array('c'=>"\0\0\100\40",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'span'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'strong'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'style'=>array('c'=>"\21",'ac'=>"\0",'dd'=>"\0",'to'=>1,'b'=>1),
		'sub'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'summary'=>array('c'=>"\0\0\0\20",'ac'=>"\204",'dd'=>"\0",'b'=>1),
		'sup'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'table'=>array('c'=>"\3\0\20",'ac'=>"\0\1\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'tbody'=>array('c'=>"\0\1",'ac'=>"\0\0\1\0\200",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('tbody','td','th','thead','tr')),
		'td'=>array('c'=>"\100\0\0\1",'ac'=>"\1",'dd'=>"\0\0\0\0\20",'b'=>1,'cp'=>array('td','th')),
		'template'=>array('c'=>"\25\0\201",'ac'=>"\0",'dd'=>"\0",'nt'=>1),
		'textarea'=>array('c'=>"\17\2",'ac'=>"\0",'dd'=>"\0",'pre'=>1,'to'=>1),
		'tfoot'=>array('c'=>"\0\1",'ac'=>"\0\0\1\0\200",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('tbody','td','th','thead','tr')),
		'th'=>array('c'=>"\0\0\0\1",'ac'=>"\1",'dd'=>"\200\104",'b'=>1,'cp'=>array('td','th')),
		'thead'=>array('c'=>"\0\1",'ac'=>"\0\0\1\0\200",'dd'=>"\0",'nt'=>1,'b'=>1),
		'time'=>array('c'=>"\7",'ac'=>"\4",'ac2'=>'@datetime','dd'=>"\0"),
		'title'=>array('c'=>"\20",'ac'=>"\0",'dd'=>"\0",'to'=>1,'b'=>1),
		'tr'=>array('c'=>"\0\1\0\0\200",'ac'=>"\0\0\1\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('td','th','tr')),
		'track'=>array('c'=>"\0\0\0\0\1",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1,'b'=>1),
		'u'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0",'fe'=>1),
		'ul'=>array('c'=>"\3",'c1'=>'li','ac'=>"\0\0\1\0\0\1",'dd'=>"\0",'nt'=>1,'b'=>1,'cp'=>array('p')),
		'var'=>array('c'=>"\7",'ac'=>"\4",'dd'=>"\0"),
		'video'=>array('c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\0\40\1",'ac29'=>'not(@src)','dd'=>"\0\0\0\0\0\4",'dd42'=>'@src','t'=>1),
		'wbr'=>array('c'=>"\5",'ac'=>"\0",'dd'=>"\0",'nt'=>1,'e'=>1,'v'=>1)
	);
	public static function closesParent(DOMElement $child, DOMElement $parent)
	{
		$parentName = $parent->nodeName;
		$childName  = $child->nodeName;
		return !empty(self::$htmlElements[$childName]['cp']) && \in_array($parentName, self::$htmlElements[$childName]['cp'], \true);
	}
	public static function disallowsText(DOMElement $element)
	{
		return self::hasProperty($element, 'nt');
	}
	public static function getAllowChildBitfield(DOMElement $element)
	{
		return self::getBitfield($element, 'ac');
	}
	public static function getCategoryBitfield(DOMElement $element)
	{
		return self::getBitfield($element, 'c');
	}
	public static function getDenyDescendantBitfield(DOMElement $element)
	{
		return self::getBitfield($element, 'dd');
	}
	public static function isBlock(DOMElement $element)
	{
		return self::hasProperty($element, 'b');
	}
	public static function isEmpty(DOMElement $element)
	{
		return self::hasProperty($element, 'e');
	}
	public static function isFormattingElement(DOMElement $element)
	{
		return self::hasProperty($element, 'fe');
	}
	public static function isTextOnly(DOMElement $element)
	{
		return self::hasProperty($element, 'to');
	}
	public static function isTransparent(DOMElement $element)
	{
		return self::hasProperty($element, 't');
	}
	public static function isVoid(DOMElement $element)
	{
		return self::hasProperty($element, 'v');
	}
	public static function preservesWhitespace(DOMElement $element)
	{
		return self::hasProperty($element, 'pre');
	}
	protected static function evaluate($query, DOMElement $element)
	{
		$xpath = new DOMXPath($element->ownerDocument);
		return $xpath->evaluate('boolean(' . $query . ')', $element);
	}
	protected static function getBitfield(DOMElement $element, $name)
	{
		$props    = self::getProperties($element);
		$bitfield = self::toBin($props[$name]);
		foreach (\array_keys(\array_filter(\str_split($bitfield, 1))) as $bitNumber)
		{
			$conditionName = $name . $bitNumber;
			if (isset($props[$conditionName]) && !self::evaluate($props[$conditionName], $element))
				$bitfield[$bitNumber] = '0';
		}
		return self::toRaw($bitfield);
	}
	protected static function getProperties(DOMElement $element)
	{
		return (isset(self::$htmlElements[$element->nodeName])) ? self::$htmlElements[$element->nodeName] : self::$htmlElements['span'];
	}
	protected static function hasProperty(DOMElement $element, $propName)
	{
		$props = self::getProperties($element);
		return !empty($props[$propName]) && (!isset($props[$propName . '?']) || self::evaluate($props[$propName . '?'], $element));
	}
	protected static function toBin($raw)
	{
		$bin = '';
		foreach (\str_split($raw, 1) as $char)
			$bin .= \strrev(\substr('0000000' . \decbin(\ord($char)), -8));
		return $bin;
	}
	protected static function toRaw($bin)
	{
		return \implode('', \array_map('chr', \array_map('bindec', \array_map('strrev', \str_split($bin, 8)))));
	}
}