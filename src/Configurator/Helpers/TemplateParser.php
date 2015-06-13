<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;
use RuntimeException;
class TemplateParser
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static $voidRegexp = '/^(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/Di';
	public static function parse($template)
	{
		$xsl = '<xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$ir = new DOMDocument;
		$ir->loadXML('<template/>');
		self::parseChildren($ir->documentElement, $dom->documentElement);
		self::normalize($ir);
		return $ir;
	}
	public static function parseEqualityExpr($expr)
	{
		$eq = '(?<equality>(?<key>@[-\\w]+|\\$\\w+|\\.)(?<operator>\\s*=\\s*)(?:(?<literal>(?<string>"[^"]*"|\'[^\']*\')|0|[1-9][0-9]*)|(?<concat>concat\\(\\s*(?&string)\\s*(?:,\\s*(?&string)\\s*)+\\)))|(?:(?<literal>(?&literal))|(?<concat>(?&concat)))(?&operator)(?<key>(?&key)))';
		$regexp = '(^(?J)\\s*' . $eq . '\\s*(?:or\\s*(?&equality)\\s*)*$)';
		if (!\preg_match($regexp, $expr))
			return \false;
		\preg_match_all("((?J)$eq)", $expr, $matches, \PREG_SET_ORDER);
		$map = [];
		foreach ($matches as $m)
		{
			$key = $m['key'];
			if (!empty($m['concat']))
			{
				\preg_match_all('(\'[^\']*\'|"[^"]*")', $m['concat'], $strings);
				$value = '';
				foreach ($strings[0] as $string)
					$value .= \substr($string, 1, -1);
			}
			else
			{
				$value = $m['literal'];
				if ($value[0] === "'" || $value[0] === '"')
					$value = \substr($value, 1, -1);
			}
			$map[$key][] = $value;
		}
		return $map;
	}
	protected static function parseChildren(DOMElement $ir, DOMElement $parent)
	{
		foreach ($parent->childNodes as $child)
		{
			switch ($child->nodeType)
			{
				case \XML_COMMENT_NODE:
					break;
				case \XML_TEXT_NODE:
					if (\trim($child->textContent) !== '')
						self::appendOutput($ir, 'literal', $child->textContent);
					break;
				case \XML_ELEMENT_NODE:
					self::parseNode($ir, $child);
					break;
				default:
					throw new RuntimeException("Cannot parse node '" . $child->nodeName . "''");
			}
		}
	}
	protected static function parseNode(DOMElement $ir, DOMElement $node)
	{
		if ($node->namespaceURI === self::XMLNS_XSL)
		{
			$methodName = 'parseXsl' . \str_replace(' ', '', \ucwords(\str_replace('-', ' ', $node->localName)));
			if (!\method_exists(__CLASS__, $methodName))
				throw new RuntimeException("Element '" . $node->nodeName . "' is not supported");
			return self::$methodName($ir, $node);
		}
		if (!\is_null($node->namespaceURI))
			throw new RuntimeException("Namespaced element '" . $node->nodeName . "' is not supported");
		$element = self::appendElement($ir, 'element');
		$element->setAttribute('name', $node->localName);
		foreach ($node->attributes as $attribute)
		{
			$irAttribute = self::appendElement($element, 'attribute');
			$irAttribute->setAttribute('name', $attribute->name);
			self::appendOutput($irAttribute, 'avt', $attribute->value);
		}
		self::parseChildren($element, $node);
	}
	protected static function parseXslApplyTemplates(DOMElement $ir, DOMElement $node)
	{
		$applyTemplates = self::appendElement($ir, 'applyTemplates');
		if ($node->hasAttribute('select'))
			$applyTemplates->setAttribute(
				'select',
				$node->getAttribute('select')
			);
	}
	protected static function parseXslAttribute(DOMElement $ir, DOMElement $node)
	{
		$attrName = $node->getAttribute('name');
		if ($attrName !== '')
		{
			$attribute = self::appendElement($ir, 'attribute');
			$attribute->setAttribute('name', $attrName);
			self::parseChildren($attribute, $node);
		}
	}
	protected static function parseXslChoose(DOMElement $ir, DOMElement $node)
	{
		$switch = self::appendElement($ir, 'switch');
		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'when') as $when)
		{
			if ($when->parentNode !== $node)
				continue;
			$case = self::appendElement($switch, 'case');
			$case->setAttribute('test', $when->getAttribute('test'));
			self::parseChildren($case, $when);
		}
		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'otherwise') as $otherwise)
		{
			if ($otherwise->parentNode !== $node)
				continue;
			$case = self::appendElement($switch, 'case');
			self::parseChildren($case, $otherwise);
			break;
		}
	}
	protected static function parseXslComment(DOMElement $ir, DOMElement $node)
	{
		$comment = self::appendElement($ir, 'comment');
		self::parseChildren($comment, $node);
	}
	protected static function parseXslCopyOf(DOMElement $ir, DOMElement $node)
	{
		$expr = $node->getAttribute('select');
		if (\preg_match('#^@([-\\w]+)$#', $expr, $m))
		{
			$switch = self::appendElement($ir, 'switch');
			$case   = self::appendElement($switch, 'case');
			$case->setAttribute('test', $expr);
			$attribute = self::appendElement($case, 'attribute');
			$attribute->setAttribute('name', $m[1]);
			self::appendOutput($attribute, 'xpath', $expr);
			return;
		}
		if ($expr === '@*')
		{
			self::appendElement($ir, 'copyOfAttributes');
			return;
		}
		throw new RuntimeException("Unsupported <xsl:copy-of/> expression '" . $expr . "'");
	}
	protected static function parseXslElement(DOMElement $ir, DOMElement $node)
	{
		$elName = $node->getAttribute('name');
		if ($elName !== '')
		{
			$element = self::appendElement($ir, 'element');
			$element->setAttribute('name', $elName);
			self::parseChildren($element, $node);
		}
	}
	protected static function parseXslIf(DOMElement $ir, DOMElement $node)
	{
		$switch = self::appendElement($ir, 'switch');
		$case   = self::appendElement($switch, 'case');
		$case->setAttribute('test', $node->getAttribute('test'));
		self::parseChildren($case, $node);
	}
	protected static function parseXslText(DOMElement $ir, DOMElement $node)
	{
		self::appendOutput($ir, 'literal', $node->textContent);
	}
	protected static function parseXslValueOf(DOMElement $ir, DOMElement $node)
	{
		self::appendOutput($ir, 'xpath', $node->getAttribute('select'));
	}
	protected static function normalize(DOMDocument $ir)
	{
		self::addDefaultCase($ir);
		self::addElementIds($ir);
		self::addCloseTagElements($ir);
		self::markEmptyElements($ir);
		self::optimize($ir);
		self::markConditionalCloseTagElements($ir);
		self::setOutputContext($ir);
		self::markBranchTables($ir);
	}
	protected static function addDefaultCase(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//switch[not(case[not(@test)])]') as $switch)
			self::appendElement($switch, 'case');
	}
	protected static function addElementIds(DOMDocument $ir)
	{
		$id = 0;
		foreach ($ir->getElementsByTagName('element') as $element)
			$element->setAttribute('id', ++$id);
	}
	protected static function addCloseTagElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$exprs = [
			'//applyTemplates[not(ancestor::attribute)]',
			'//comment',
			'//element',
			'//output[not(ancestor::attribute)]'
		];
		foreach ($xpath->query(\implode('|', $exprs)) as $node)
		{
			$parentElementId = self::getParentElementId($node);
			if (isset($parentElementId))
				$node->parentNode
				     ->insertBefore($ir->createElement('closeTag'), $node)
				     ->setAttribute('id', $parentElementId);
			if ($node->nodeName === 'element')
			{
				$id = $node->getAttribute('id');
				self::appendElement($node, 'closeTag')->setAttribute('id', $id);
			}
		}
	}
	protected static function markConditionalCloseTagElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($ir->getElementsByTagName('closeTag') as $closeTag)
		{
			$id = $closeTag->getAttribute('id');
			$query = 'ancestor::switch/following-sibling::*/descendant-or-self::closeTag[@id = "' . $id . '"]';
			foreach ($xpath->query($query, $closeTag) as $following)
			{
				$following->setAttribute('check', '');
				$closeTag->setAttribute('set', '');
			}
		}
	}
	protected static function markEmptyElements(DOMDocument $ir)
	{
		foreach ($ir->getElementsByTagName('element') as $element)
		{
			$elName = $element->getAttribute('name');
			if (\strpos($elName, '{') !== \false)
				$element->setAttribute('void', 'maybe');
			elseif (\preg_match(self::$voidRegexp, $elName))
				$element->setAttribute('void', 'yes');
			$isEmpty = self::isEmpty($element);
			if ($isEmpty === 'yes' || $isEmpty === 'maybe')
				$element->setAttribute('empty', $isEmpty);
		}
	}
	protected static function getParentElementId(DOMNode $node)
	{
		$parentNode = $node->parentNode;
		while (isset($parentNode))
		{
			if ($parentNode->nodeName === 'element')
				return $parentNode->getAttribute('id');
			$parentNode = $parentNode->parentNode;
		}
	}
	protected static function setOutputContext(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($ir->getElementsByTagName('output') as $output)
		{
			$escape = ($xpath->evaluate('boolean(ancestor::attribute)', $output))
			        ? 'attribute'
			        : 'text';
			$output->setAttribute('escape', $escape);
		}
	}
	protected static function optimize(DOMDocument $ir)
	{
		$xml = $ir->saveXML();
		$remainingLoops = 10;
		do
		{
			$old = $xml;
			self::optimizeCloseTagElements($ir);
			$xml = $ir->saveXML();
		}
		while (--$remainingLoops > 0 && $xml !== $old);
		self::removeCloseTagSiblings($ir);
		self::removeContentFromVoidElements($ir);
		self::mergeConsecutiveLiteralOutputElements($ir);
		self::removeEmptyDefaultCases($ir);
	}
	protected static function removeCloseTagSiblings(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[not(case[not(closeTag)])]/following-sibling::closeTag';
		foreach ($xpath->query($query) as $closeTag)
			$closeTag->parentNode->removeChild($closeTag);
	}
	protected static function removeEmptyDefaultCases(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//case[not(@test | node())]') as $case)
			$case->parentNode->removeChild($case);
	}
	protected static function mergeConsecutiveLiteralOutputElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//output[@type="literal"]') as $output)
			while ($output->nextSibling
				&& $output->nextSibling->nodeName === 'output'
				&& $output->nextSibling->getAttribute('type') === 'literal')
			{
				$output->nodeValue
					= \htmlspecialchars($output->nodeValue . $output->nextSibling->nodeValue);
				$output->parentNode->removeChild($output->nextSibling);
			}
	}
	protected static function optimizeCloseTagElements(DOMDocument $ir)
	{
		self::cloneCloseTagElementsIntoSwitch($ir);
		self::cloneCloseTagElementsOutOfSwitch($ir);
		self::removeRedundantCloseTagElementsInSwitch($ir);
		self::removeRedundantCloseTagElements($ir);
	}
	protected static function cloneCloseTagElementsIntoSwitch(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[name(following-sibling::*) = "closeTag"]';
		foreach ($xpath->query($query) as $switch)
		{
			$closeTag = $switch->nextSibling;
			foreach ($switch->childNodes as $case)
				if (!$case->lastChild || $case->lastChild->nodeName !== 'closeTag')
					$case->appendChild($closeTag->cloneNode());
		}
	}
	protected static function cloneCloseTagElementsOutOfSwitch(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[not(preceding-sibling::closeTag)]';
		foreach ($xpath->query($query) as $switch)
		{
			foreach ($switch->childNodes as $case)
				if (!$case->firstChild || $case->firstChild->nodeName !== 'closeTag')
					continue 2;
			$switch->parentNode->insertBefore($switch->lastChild->firstChild->cloneNode(), $switch);
		}
	}
	protected static function removeRedundantCloseTagElementsInSwitch(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		$query = '//switch[name(following-sibling::*) = "closeTag"]';
		foreach ($xpath->query($query) as $switch)
			foreach ($switch->childNodes as $case)
				while ($case->lastChild && $case->lastChild->nodeName === 'closeTag')
					$case->removeChild($case->lastChild);
	}
	protected static function removeRedundantCloseTagElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//closeTag') as $closeTag)
		{
			$id    = $closeTag->getAttribute('id');
			$query = 'following-sibling::*/descendant-or-self::closeTag[@id="' . $id . '"]';
			foreach ($xpath->query($query, $closeTag) as $dupe)
				$dupe->parentNode->removeChild($dupe);
		}
	}
	protected static function removeContentFromVoidElements(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//element[@void="yes"]') as $element)
		{
			$id    = $element->getAttribute('id');
			$query = './/closeTag[@id="' . $id . '"]/following-sibling::*';
			foreach ($xpath->query($query, $element) as $node)
				$node->parentNode->removeChild($node);
		}
	}
	protected static function markBranchTables(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);
		foreach ($xpath->query('//switch[case[2][@test]]') as $switch)
		{
			$key = \null;
			$branchValues = [];
			foreach ($switch->childNodes as $i => $case)
			{
				if (!$case->hasAttribute('test'))
					continue;
				$map = self::parseEqualityExpr($case->getAttribute('test'));
				if ($map === \false)
					continue 2;
				if (\count($map) !== 1)
					continue 2;
				if (isset($key) && $key !== \key($map))
					continue 2;
				$key = \key($map);
				$branchValues[$i] = \end($map);
			}
			$switch->setAttribute('branch-key', $key);
			foreach ($branchValues as $i => $values)
			{
				\sort($values);
				$switch->childNodes->item($i)->setAttribute('branch-values', \serialize($values));
			}
		}
	}
	protected static function appendElement(DOMElement $parentNode, $name, $value = '')
	{
		if ($value === '')
			$element = $parentNode->ownerDocument->createElement($name);
		else
			$element = $parentNode->ownerDocument->createElement($name, $value);
		$parentNode->appendChild($element);
		return $element;
	}
	protected static function appendOutput(DOMElement $ir, $type, $content)
	{
		if ($type === 'avt')
		{
			foreach (AVTHelper::parse($content) as $token)
			{
				$type = ($token[0] === 'expression') ? 'xpath' : 'literal';
				self::appendOutput($ir, $type, $token[1]);
			}
			return;
		}
		if ($type === 'xpath')
			$content = \trim($content);
		if ($type === 'literal' && $content === '')
			return;
		self::appendElement($ir, 'output', \htmlspecialchars($content))
			->setAttribute('type', $type);
	}
	protected static function isEmpty(DOMElement $ir)
	{
		$xpath = new DOMXPath($ir->ownerDocument);
		if ($xpath->evaluate('count(comment | element | output[@type="literal"])', $ir))
			return 'no';
		$cases = [];
		foreach ($xpath->query('switch/case', $ir) as $case)
			$cases[self::isEmpty($case)] = 1;
		if (isset($cases['maybe']))
			return 'maybe';
		if (isset($cases['no']))
		{
			if (!isset($cases['yes']))
				return 'no';
			return 'maybe';
		}
		if ($xpath->evaluate('count(applyTemplates | output[@type="xpath"])', $ir))
			return 'maybe';
		return 'yes';
	}
}