<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
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

	protected $leafNodes = [];

	protected $preservesNewLines = \false;

	protected $rootBitfields = [];

	protected $rootNodes = [];

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
		$branchBitfields = [];

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

	protected static $htmlElements = [
		'a'=>['c'=>"\17",'ac'=>"\0",'dd'=>"\10",'t'=>1,'fe'=>1],
		'abbr'=>['c'=>"\7",'ac'=>"\4"],
		'address'=>['c'=>"\3\10",'ac'=>"\1",'dd'=>"\100\12",'b'=>1,'cp'=>['p']],
		'area'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1],
		'article'=>['c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'aside'=>['c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>['p']],
		'audio'=>['c'=>"\57",'c3'=>'@controls','c1'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1],
		'b'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'base'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'bdi'=>['c'=>"\7",'ac'=>"\4"],
		'bdo'=>['c'=>"\7",'ac'=>"\4"],
		'blockquote'=>['c'=>"\3\1",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'body'=>['c'=>"\0\1\2",'ac'=>"\1",'b'=>1],
		'br'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1],
		'button'=>['c'=>"\17",'ac'=>"\4",'dd'=>"\10"],
		'canvas'=>['c'=>"\47",'ac'=>"\0",'t'=>1],
		'caption'=>['c'=>"\200",'ac'=>"\1",'dd'=>"\0\0\0\10",'b'=>1],
		'cite'=>['c'=>"\7",'ac'=>"\4"],
		'code'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'col'=>['c'=>"\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'colgroup'=>['c'=>"\200",'ac'=>"\0\0\4",'ac18'=>'not(@span)','nt'=>1,'e'=>1,'e0'=>'@span','b'=>1],
		'data'=>['c'=>"\7",'ac'=>"\4"],
		'datalist'=>['c'=>"\5",'ac'=>"\4\0\0\1"],
		'dd'=>['c'=>"\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>['dd','dt']],
		'del'=>['c'=>"\5",'ac'=>"\0",'t'=>1],
		'dfn'=>['c'=>"\7\0\0\0\2",'ac'=>"\4",'dd'=>"\0\0\0\0\2"],
		'div'=>['c'=>"\3",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'dl'=>['c'=>"\3",'ac'=>"\0\40\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'dt'=>['c'=>"\0\0\20",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>['dd','dt']],
		'em'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'embed'=>['c'=>"\57",'nt'=>1,'e'=>1,'v'=>1],
		'fieldset'=>['c'=>"\3\1",'ac'=>"\1\0\0\2",'b'=>1,'cp'=>['p']],
		'figcaption'=>['c'=>"\0\0\0\0\40",'ac'=>"\1",'b'=>1],
		'figure'=>['c'=>"\3\1",'ac'=>"\1\0\0\0\40",'b'=>1],
		'footer'=>['c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>['p']],
		'form'=>['c'=>"\3\0\0\0\1",'ac'=>"\1",'dd'=>"\0\0\0\0\1",'b'=>1,'cp'=>['p']],
		'h1'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h2'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h3'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h4'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h5'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'h6'=>['c'=>"\103",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'head'=>['c'=>"\0\0\2",'ac'=>"\20",'nt'=>1,'b'=>1],
		'header'=>['c'=>"\3\30\1",'ac'=>"\1",'dd'=>"\0\20",'b'=>1,'cp'=>['p']],
		'hr'=>['c'=>"\1",'nt'=>1,'e'=>1,'v'=>1,'b'=>1,'cp'=>['p']],
		'html'=>['c'=>"\0",'ac'=>"\0\0\2",'nt'=>1,'b'=>1],
		'i'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'iframe'=>['c'=>"\57",'nt'=>1,'e'=>1,'to'=>1],
		'img'=>['c'=>"\57",'c3'=>'@usemap','nt'=>1,'e'=>1,'v'=>1],
		'input'=>['c'=>"\17",'c3'=>'@type!="hidden"','c1'=>'@type!="hidden"','nt'=>1,'e'=>1,'v'=>1],
		'ins'=>['c'=>"\7",'ac'=>"\0",'t'=>1],
		'kbd'=>['c'=>"\7",'ac'=>"\4"],
		'keygen'=>['c'=>"\17",'nt'=>1,'e'=>1,'v'=>1],
		'label'=>['c'=>"\17\0\0\100",'ac'=>"\4",'dd'=>"\0\0\0\100"],
		'legend'=>['c'=>"\0\0\0\2",'ac'=>"\4",'b'=>1],
		'li'=>['c'=>"\0\0\0\0\20",'ac'=>"\1",'b'=>1,'cp'=>['li']],
		'link'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'main'=>['c'=>"\3\20\0\200",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'map'=>['c'=>"\7",'ac'=>"\0",'t'=>1],
		'mark'=>['c'=>"\7",'ac'=>"\4"],
		'meta'=>['c'=>"\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'meter'=>['c'=>"\7\100\0\40",'ac'=>"\4",'dd'=>"\0\0\0\40"],
		'nav'=>['c'=>"\3\2",'ac'=>"\1",'dd'=>"\0\0\0\200",'b'=>1,'cp'=>['p']],
		'noscript'=>['c'=>"\25\0\100",'ac'=>"\0",'dd'=>"\0\0\100",'t'=>1],
		'object'=>['c'=>"\57",'c3'=>'@usemap','ac'=>"\0\0\0\20",'t'=>1],
		'ol'=>['c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'optgroup'=>['c'=>"\0\200",'ac'=>"\0\40\0\1",'nt'=>1,'b'=>1,'cp'=>['optgroup','option']],
		'option'=>['c'=>"\0\200\0\1",'e'=>1,'e0'=>'@label and @value','to'=>1,'b'=>1,'cp'=>['option']],
		'output'=>['c'=>"\7",'ac'=>"\4"],
		'p'=>['c'=>"\3",'ac'=>"\4",'b'=>1,'cp'=>['p']],
		'param'=>['c'=>"\0\0\0\20",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'pre'=>['c'=>"\3",'ac'=>"\4",'pre'=>1,'b'=>1,'cp'=>['p']],
		'progress'=>['c'=>"\7\100\40",'ac'=>"\4",'dd'=>"\0\0\40"],
		'q'=>['c'=>"\7",'ac'=>"\4"],
		'rb'=>['c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rt','rtc']],
		'rp'=>['c'=>"\0\4",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rtc']],
		'rt'=>['c'=>"\0\4\0\0\10",'ac'=>"\4",'b'=>1,'cp'=>['rb','rp','rt']],
		'rtc'=>['c'=>"\0\4",'ac'=>"\4\0\0\0\10",'b'=>1,'cp'=>['rb','rp','rt','rtc']],
		'ruby'=>['c'=>"\7",'ac'=>"\4\4"],
		's'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'samp'=>['c'=>"\7",'ac'=>"\4"],
		'script'=>['c'=>"\25\40",'e'=>1,'e0'=>'@src','to'=>1],
		'section'=>['c'=>"\3\2",'ac'=>"\1",'b'=>1,'cp'=>['p']],
		'select'=>['c'=>"\17",'ac'=>"\0\240",'nt'=>1],
		'small'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'source'=>['c'=>"\0\0\200",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'span'=>['c'=>"\7",'ac'=>"\4"],
		'strong'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'style'=>['c'=>"\20",'to'=>1,'b'=>1],
		'sub'=>['c'=>"\7",'ac'=>"\4"],
		'sup'=>['c'=>"\7",'ac'=>"\4"],
		'table'=>['c'=>"\3\0\0\10",'ac'=>"\200\40",'nt'=>1,'b'=>1,'cp'=>['p']],
		'tbody'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>['tbody','tfoot','thead']],
		'td'=>['c'=>"\0\1\10",'ac'=>"\1",'b'=>1,'cp'=>['td','th']],
		'template'=>['c'=>"\25\40\4",'ac'=>"\21"],
		'textarea'=>['c'=>"\17",'pre'=>1],
		'tfoot'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1,'cp'=>['tbody','thead']],
		'th'=>['c'=>"\0\0\10",'ac'=>"\1",'dd'=>"\100\2\1",'b'=>1,'cp'=>['td','th']],
		'thead'=>['c'=>"\200",'ac'=>"\0\40\0\0\4",'nt'=>1,'b'=>1],
		'time'=>['c'=>"\7",'ac'=>"\4"],
		'title'=>['c'=>"\20",'to'=>1,'b'=>1],
		'tr'=>['c'=>"\200\0\0\0\4",'ac'=>"\0\40\10",'nt'=>1,'b'=>1,'cp'=>['tr']],
		'track'=>['c'=>"\0\0\0\4",'nt'=>1,'e'=>1,'v'=>1,'b'=>1],
		'u'=>['c'=>"\7",'ac'=>"\4",'fe'=>1],
		'ul'=>['c'=>"\3",'ac'=>"\0\40\0\0\20",'nt'=>1,'b'=>1,'cp'=>['p']],
		'var'=>['c'=>"\7",'ac'=>"\4"],
		'video'=>['c'=>"\57",'c3'=>'@controls','ac'=>"\0\0\200\4",'ac23'=>'not(@src)','ac26'=>'@src','t'=>1],
		'wbr'=>['c'=>"\5",'nt'=>1,'e'=>1,'v'=>1]
	];

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