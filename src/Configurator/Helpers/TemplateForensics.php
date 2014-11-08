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

class TemplateForensics
{
	protected $allowChildBitfield = "\0";

	protected $allowsChildElements = \true;

	protected $allowsText = \true;

	protected $contentBitfield = "\0";

	protected $denyDescendantBitfield = "\0";

	protected $dom;

	protected $hasElements = \false;

	protected $hasRootText = \false;

	protected $isBlock = \false;

	protected $isEmpty = \true;

	protected $isFormattingElement = \false;

	protected $isPassthrough = \false;

	protected $isTransparent = \false;

	protected $isVoid = \true;

	protected $leafNodes = array();

	protected $preservesNewLines = \false;

	protected $rootBitfields = array();

	protected $rootNodes = array();

	protected $xpath;

	public function __construct($template)
	{
		$this->dom   = TemplateHelper::loadTemplate($template);
		$this->xpath = new DOMXPath($this->dom);

		$this->analyseRootNodes();
		$this->analyseBranches();
		$this->analyseContent();
	}

	public function allowsChild(self $child)
	{
		if (!$this->allowsDescendant($child))
			return \false;

		foreach ($child->rootBitfields as $rootBitfield)
			if (!self::match($rootBitfield, $this->allowChildBitfield))
				return \false;

		if (!$this->allowsText && $child->hasRootText)
			return \false;

		return \true;
	}

	public function allowsDescendant(self $descendant)
	{
		if (self::match($descendant->contentBitfield, $this->denyDescendantBitfield))
			return \false;

		if (!$this->allowsChildElements && $descendant->hasElements)
			return \false;

		return \true;
	}

	public function allowsChildElements()
	{
		return $this->allowsChildElements;
	}

	public function allowsText()
	{
		return $this->allowsText;
	}

	public function closesParent(self $parent)
	{
		foreach ($this->rootNodes as $rootName)
		{
			if (empty(self::$htmlElements[$rootName]['cp']))
				continue;

			foreach ($parent->leafNodes as $leafName)
				if (\in_array($leafName, self::$htmlElements[$rootName]['cp'], \true))
					return \true;
		}

		return \false;
	}

	public function getDOM()
	{
		return $this->dom;
	}

	public function isBlock()
	{
		return $this->isBlock;
	}

	public function isFormattingElement()
	{
		return $this->isFormattingElement;
	}

	public function isEmpty()
	{
		return $this->isEmpty;
	}

	public function isPassthrough()
	{
		return $this->isPassthrough;
	}

	public function isTransparent()
	{
		return $this->isTransparent;
	}

	public function isVoid()
	{
		return $this->isVoid;
	}

	public function preservesNewLines()
	{
		return $this->preservesNewLines;
	}

	protected function analyseContent()
	{
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]';

		foreach ($this->xpath->query($query) as $node)
		{
			$this->contentBitfield |= $this->getBitfield($node->localName, 'c', $node);
			$this->hasElements = \true;
		}

		$this->isPassthrough = (bool) $this->xpath->evaluate('count(//xsl:apply-templates)');
	}

	protected function analyseRootNodes()
	{
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"][not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';

		foreach ($this->xpath->query($query) as $node)
		{
			$elName = $node->localName;

			$this->rootNodes[] = $elName;

			if (!isset(self::$htmlElements[$elName]))
				$elName = 'span';

			if ($this->hasProperty($elName, 'b', $node))
				$this->isBlock = \true;

			$this->rootBitfields[] = $this->getBitfield($elName, 'c', $node);
		}

		$predicate = '[not(ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"])]';

		$predicate .= '[not(ancestor::xsl:attribute | ancestor::xsl:comment | ancestor::xsl:variable)]';

		$query = '//text()[normalize-space() != ""]' . $predicate
		       . '|//xsl:text[normalize-space() != ""]' . $predicate
		       . '|//xsl:value-of' . $predicate;

		if ($this->evaluate($query, $this->dom->documentElement))
			$this->hasRootText = \true;
	}

	protected function analyseBranches()
	{
		$branchBitfields = array();

		$isFormattingElement = \true;

		$this->isTransparent = \true;

		foreach ($this->getXSLElements('apply-templates') as $applyTemplates)
		{
			$nodes = $this->xpath->query(
				'ancestor::*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]',
				$applyTemplates
			);

			$allowsChildElements = \true;

			$allowsText = \true;

			$branchBitfield = self::$htmlElements['div']['ac'];

			$isEmpty = \false;

			$isVoid = \false;

			$leafNode = \null;

			$preservesNewLines = \false;

			foreach ($nodes as $node)
			{
				$elName = $leafNode = $node->localName;

				if (!isset(self::$htmlElements[$elName]))
					$elName = 'span';

				if ($this->hasProperty($elName, 'v', $node))
					$isVoid = \true;

				if ($this->hasProperty($elName, 'e', $node))
					$isEmpty = \true;

				if (!$this->hasProperty($elName, 't', $node))
				{
					$branchBitfield = "\0";

					$this->isTransparent = \false;
				}

				if (!$this->hasProperty($elName, 'fe', $node)
				 && !$this->isFormattingSpan($node))
					$isFormattingElement = \false;

				$allowsChildElements = !$this->hasProperty($elName, 'to', $node);

				$allowsText = !$this->hasProperty($elName, 'nt', $node);

				$branchBitfield |= $this->getBitfield($elName, 'ac', $node);

				$this->denyDescendantBitfield |= $this->getBitfield($elName, 'dd', $node);

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
					$preservesNewLines = ($match === 'pre');
			}

			$branchBitfields[] = $branchBitfield;

			if (isset($leafNode))
				$this->leafNodes[] = $leafNode;

			if (!$allowsChildElements)
				$this->allowsChildElements = \false;

			if (!$allowsText)
				$this->allowsText = \false;

			if (!$isEmpty)
				$this->isEmpty = \false;

			if (!$isVoid)
				$this->isVoid = \false;

			if ($preservesNewLines)
				$this->preservesNewLines = \true;
		}

		if (empty($branchBitfields))
			$this->isTransparent = \false;
		else
		{
			$this->allowChildBitfield = $branchBitfields[0];

			foreach ($branchBitfields as $branchBitfield)
				$this->allowChildBitfield &= $branchBitfield;

			if (!empty($this->leafNodes))
				$this->isFormattingElement = $isFormattingElement;
		}
	}

	protected function evaluate($query, DOMElement $node)
	{
		return $this->xpath->evaluate('boolean(' . $query . ')', $node);
	}

	protected function getXSLElements($elName)
	{
		return $this->dom->getElementsByTagNameNS('http://www.w3.org/1999/XSL/Transform', $elName);
	}

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
					continue;

				$n = $byteNumber * 8 + $bitNumber;

				if (isset(self::$htmlElements[$elName][$k . $n]))
				{
					$xpath = self::$htmlElements[$elName][$k . $n];

					if (!$this->evaluate($xpath, $node))
					{
						$byteValue ^= $bitValue;

						$bitfield[$byteNumber] = \chr($byteValue);
					}
				}
			}
		}

		return $bitfield;
	}

	protected function hasProperty($elName, $propName, DOMElement $node)
	{
		if (!empty(self::$htmlElements[$elName][$propName]))
			if (!isset(self::$htmlElements[$elName][$propName . '0'])
			 || $this->evaluate(self::$htmlElements[$elName][$propName . '0'], $node))
				return \true;

		return \false;
	}

	protected static function match($bitfield1, $bitfield2)
	{
		return (\trim($bitfield1 & $bitfield2, "\0") !== '');
	}
}