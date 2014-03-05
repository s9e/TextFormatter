<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

class TemplateParser
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* @var string Regexp that matches the names of all void elements
	* @link http://www.w3.org/TR/html-markup/syntax.html#void-elements
	*/
	public static $voidRegexp = '/^(?:area|base|br|col|command|embed|hr|img|input|keygen|link|meta|param|source|track|wbr)$/Di';

	/**
	* Parse a stylesheet into an internal representation
	*
	* @param  string      $xsl Original stylesheet
	* @return DOMDocument      Internal representation
	*/
	public static function parse($xsl)
	{
		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$ir = new DOMDocument;
		$ir->loadXML('<stylesheet/>');
		self::parseChildren($ir->documentElement, $dom->documentElement);

		self::normalize($ir);

		return $ir;
	}

	//==========================================================================
	// General parsing
	//==========================================================================

	/**
	* Parse all the children of a given element
	*
	* @param  DOMElement $ir     Node in the internal representation that represents the parent node
	* @param  DOMElement $parent Parent node
	* @return void
	*/
	protected static function parseChildren(DOMElement $ir, DOMElement $parent)
	{
		foreach ($parent->childNodes as $child)
		{
			switch ($child->nodeType)
			{
				case XML_COMMENT_NODE:
					// Do nothing
					break;

				case XML_TEXT_NODE:
					self::appendOutput($ir, 'literal', $child->textContent);
					break;

				case XML_ELEMENT_NODE:
					self::parseNode($ir, $child);
					break;

				default:
					throw new RuntimeException("Cannot parse node '" . $child->nodeName . "''");
			}
		}
	}

	/**
	* Parse a given node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node Node to parse
	* @return void
	*/
	protected static function parseNode(DOMElement $ir, DOMElement $node)
	{
		// XSL elements are parsed by the corresponding parseXsl* method
		if ($node->namespaceURI === self::XMLNS_XSL)
		{
			$methodName = 'parseXsl' . str_replace(' ', '', ucwords(str_replace('-', ' ', $node->localName)));

			if (!method_exists(__CLASS__, $methodName))
			{
				throw new RuntimeException("Element '" . $node->nodeName . "' is not supported");
			}

			return self::$methodName($ir, $node);
		}

		// Namespaced elements are not supported
		if (!is_null($node->namespaceURI))
		{
			throw new RuntimeException("Namespaced element '" . $node->nodeName . "' is not supported");
		}

		// Create an <element/> with a name attribute equal to given node's name
		$element = self::appendElement($ir, 'element');
		$element->setAttribute('name', $node->localName);

		// Append an <attribute/> element for each of this node's attribute
		foreach ($node->attributes as $attribute)
		{
			$irAttribute = self::appendElement($element, 'attribute');
			$irAttribute->setAttribute('name', $attribute->name);

			// Append an <output/> element to represent the attribute's value
			self::appendOutput($irAttribute, 'avt', $attribute->value);
		}

		// Parse the content of this node
		self::parseChildren($element, $node);
	}

	//==========================================================================
	// XSL parsing
	//==========================================================================

	/**
	* Parse an <xsl:apply-templates/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:apply-templates/> node
	* @return void
	*/
	protected static function parseXslApplyTemplates(DOMElement $ir, DOMElement $node)
	{
		$applyTemplates = self::appendElement($ir, 'applyTemplates');

		if ($node->hasAttribute('select'))
		{
			$applyTemplates->setAttribute(
				'select',
				$node->getAttribute('select')
			);
		}
	}

	/**
	* Parse an <xsl:attribute/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:attribute/> node
	* @return void
	*/
	protected static function parseXslAttribute(DOMElement $ir, DOMElement $node)
	{
		$attrName = $node->getAttribute('name');

		if ($attrName !== '')
		{
			$attribute = self::appendElement($ir, 'attribute');

			// Copy this attribute's name
			$attribute->setAttribute('name', $attrName);

			// Parse this attribute's content
			self::parseChildren($attribute, $node);
		}
	}

	/**
	* Parse an <xsl:choose/> node and its <xsl:when/> and <xsl:otherwise/> children into the
	* internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:choose/> node
	* @return void
	*/
	protected static function parseXslChoose(DOMElement $ir, DOMElement $node)
	{
		$switch = self::appendElement($ir, 'switch');

		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'when') as $when)
		{
			// Only children of current node, exclude other descendants
			if ($when->parentNode !== $node)
			{
				continue;
			}

			// Create a <case/> element with the original test condition in @test
			$case = self::appendElement($switch, 'case');
			$case->setAttribute('test', $when->getAttribute('test'));

			// Parse this branch's content
			self::parseChildren($case, $when);
		}

		// Add the default branch, which is presumed to be last
		foreach ($node->getElementsByTagNameNS(self::XMLNS_XSL, 'otherwise') as $otherwise)
		{
			// Only children of current node, exclude other descendants
			if ($otherwise->parentNode !== $node)
			{
				continue;
			}

			$case = self::appendElement($switch, 'case');

			// Parse this branch's content
			self::parseChildren($case, $otherwise);

			// There should be only one <xsl:otherwise/> but we'll break anyway
			break;
		}
	}

	/**
	* Parse an <xsl:comment/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:comment/> node
	* @return void
	*/
	protected static function parseXslComment(DOMElement $ir, DOMElement $node)
	{
		$comment = self::appendElement($ir, 'comment');

		// Parse this branch's content
		self::parseChildren($comment, $node);
	}

	/**
	* Parse an <xsl:copy-of/> node into the internal representation
	*
	* NOTE: only attributes are supported
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:copy-of/> node
	* @return void
	*/
	protected static function parseXslCopyOf(DOMElement $ir, DOMElement $node)
	{
		$expr = $node->getAttribute('select');

		// <xsl:copy-of select="@foo"/>
		if (preg_match('#^@([-\\w]+)$#', $expr, $m))
		{
			// Create a switch element in the IR
			$switch = self::appendElement($ir, 'switch');
			$case   = self::appendElement($switch, 'case');
			$case->setAttribute('test', $expr);

			// Append an attribute element
			$attribute = self::appendElement($case, 'attribute');
			$attribute->setAttribute('name', $m[1]);

			// Set the attribute's content, which is simply the copied attribute's value
			self::appendOutput($attribute, 'xpath', $expr);

			return;
		}

		// <xsl:copy-of select="@*"/>
		if ($expr === '@*')
		{
			self::appendElement($ir, 'copyOfAttributes');

			return;
		}

		throw new RuntimeException("Unsupported <xsl:copy-of/> expression '" . $expr . "'");
	}

	/**
	* Parse an <xsl:element/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:element/> node
	* @return void
	*/
	protected static function parseXslElement(DOMElement $ir, DOMElement $node)
	{
		$elName = $node->getAttribute('name');

		if ($elName !== '')
		{
			$element = self::appendElement($ir, 'element');

			// Copy this element's name
			$element->setAttribute('name', $elName);

			// Parse this element's content
			self::parseChildren($element, $node);
		}
	}

	/**
	* Parse an <xsl:if/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:if/> node
	* @return void
	*/
	protected static function parseXslIf(DOMElement $ir, DOMElement $node)
	{
		// An <xsl:if/> is represented by a <switch/> with only one <case/>
		$switch = self::appendElement($ir, 'switch');
		$case   = self::appendElement($switch, 'case');
		$case->setAttribute('test', $node->getAttribute('test'));

		// Parse this branch's content
		self::parseChildren($case, $node);
	}

	/**
	* Parse an <xsl:output/> node into the internal representation
	*
	* NOTE: this method expects the <xsl:output/> node to be the child of an <xsl:stylesheet/>
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:output/> node
	* @return void
	*/
	protected static function parseXslOutput(DOMElement $ir, DOMElement $node)
	{
		// Copy the output method
		$ir->setAttribute('outputMethod', $node->getAttribute('method'));
	}

	/**
	* Parse an <xsl:param/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:param/> node
	* @return void
	*/
	protected static function parseXslParam(DOMElement $ir, DOMElement $node)
	{
		$param = self::appendElement($ir, 'param');
		$param->setAttribute('name', $node->getAttribute('name'));

		if ($node->hasAttribute('select'))
		{
			$param->setAttribute('select', $node->getAttribute('select'));
		}
	}

	/**
	* Parse an <xsl:template/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:template/> node
	* @return void
	*/
	protected static function parseXslTemplate(DOMElement $ir, DOMElement $node)
	{
		// Append a <template/> node in the IR
		$template = self::appendElement($ir, 'template');

		// Parse the match expression
		$match   = $node->getAttribute('match');
		$pos     = 0;
		$len     = strlen($match);
		$lastPos = 0;

		$inBrackets    = 0;
		$inParentheses = 0;

		$exprs = [];
		$expr  = '';

		do
		{
			$pos += strcspn($match, '\'"([|])', $pos);
			if ($pos >= $len)
			{
				break;
			}

			switch ($match[$pos])
			{
				case '|':
					if (!$inBrackets && !$inParentheses)
					{
						$exprs[] = substr($match, $lastPos, $pos - $lastPos);
						$lastPos = 1 + $pos;
					}
					break;

				case '"':
				case "'":
					$pos += 1 + strcspn($match, $match[$pos], 1 + $pos);
					break;

				case '[':
					++$inBrackets;
					break;

				case ']':
					--$inBrackets;
					break;

				case '(':
					++$inParentheses;
					break;

				case ')':
					--$inParentheses;
					break;
			}
		}
		while (++$pos < $len);

		// Add the last expression
		$exprs[] = substr($match, $lastPos);

		// Sort the match alphabetically
		sort($exprs);

		// Append the match to the IR
		foreach ($exprs as $expr)
		{
			$match = htmlspecialchars(trim($expr));

			/**
			* Compute this template's priority
			*
			* @link http://www.w3.org/TR/xslt#conflict
			*/
			if (preg_match('#^(?:\\w+:)?[-\\w]+$#', $match))
			{
				// QName such as "FOO" or "foo:BAR"
				$priority = 0;
			}
			elseif (preg_match('#^\\w+:\\*#', $match))
			{
				// NCName:* such as "html:*"
				$priority = -0.25;
			}
			else
			{
				// Default priority
				$priority = 0.5;
			}

			// Append a <match/> element to the IR, with its priority
			self::appendElement($template, 'match', $match)->setAttribute('priority', $priority);
		}

		// Parse this template's content
		self::parseChildren($template, $node);
	}

	/**
	* Parse an <xsl:text/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:text/> node
	* @return void
	*/
	protected static function parseXslText(DOMElement $ir, DOMElement $node)
	{
		self::appendOutput($ir, 'literal', $node->textContent);
	}

	/**
	* Parse an <xsl:value-of/> node into the internal representation
	*
	* @param  DOMElement $ir   Node in the internal representation that represents the node's parent
	* @param  DOMElement $node <xsl:value-of/> node
	* @return void
	*/
	protected static function parseXslValueOf(DOMElement $ir, DOMElement $node)
	{
		self::appendOutput($ir, 'xpath', $node->getAttribute('select'));
	}

	//==========================================================================
	// IR optimization
	//==========================================================================

	/**
	* Normalize an IR
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected static function normalize(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);

		// Add an empty default <case/> to <switch/> nodes that don't have one
		foreach ($xpath->query('//switch[not(case[not(@test)])]') as $switch)
		{
			self::appendElement($switch, 'case');
		}

		// Add an id attribute to <element/> nodes
		$id = 0;
		foreach ($ir->getElementsByTagName('element') as $element)
		{
			$element->setAttribute('id', ++$id);
		}

		// Add <closeTag/> elements to the internal representation, everywhere an open start tag
		// should be closed
		$query = '//applyTemplates[not(ancestor::attribute)]'
		       . '|'
		       . '//comment'
		       . '|'
		       . '//element'
		       . '|'
		       . '//output[not(ancestor::attribute)]';

		foreach ($xpath->query($query) as $node)
		{
			// Climb through this node's ascendants to find the closest <element/>, if applicable
			$parentNode = $node->parentNode;
			while ($parentNode)
			{
				if ($parentNode->nodeName === 'element')
				{
					$node->parentNode->insertBefore(
						$ir->createElement('closeTag'),
						$node
					)->setAttribute('id', $parentNode->getAttribute('id'));

					break;
				}

				$parentNode = $parentNode->parentNode;
			}

			// Append a <closeTag/> to <element/> nodes to ensure that empty elements get closed
			if ($node->nodeName === 'element')
			{
				self::appendElement($node, 'closeTag')
					->setAttribute('id', $node->getAttribute('id'));
			}
		}

		// Mark void elements and elements with no content
		foreach ($ir->getElementsByTagName('element') as $element)
		{
			$elName = $element->getAttribute('name');

			// Test whether this element is (maybe) void
			if (strpos($elName, '{') !== false)
			{
				// Dynamic element names must be checked at runtime
				$element->setAttribute('void', 'maybe');
			}
			elseif (preg_match(self::$voidRegexp, $elName))
			{
				// Static element names can be checked right now
				$element->setAttribute('void', 'yes');
			}

			// Find whether this element is empty
			$isEmpty = self::isEmpty($element);

			if ($isEmpty === 'yes' || $isEmpty === 'maybe')
			{
				$element->setAttribute('empty', $isEmpty);
			}
		}

		// Optimize the IR
		self::optimize($ir);

		// Mark conditional <closeTag/> nodes
		foreach ($ir->getElementsByTagName('closeTag') as $closeTag)
		{
			$id = $closeTag->getAttribute('id');

			// For each <switch/> ancestor, look for a <closeTag/> and that is either a sibling or
			// the descendant of a sibling, and that matches the id
			$query = 'ancestor::switch/'
			       . 'following-sibling::*/'
			       . 'descendant-or-self::closeTag[@id = "' . $id . '"]';

			foreach ($xpath->query($query, $closeTag) as $following)
			{
				// Mark following <closeTag/> nodes to indicate that the status of this tag must
				// be checked before it is closed
				$following->setAttribute('check', '');

				// Mark the current <closeTag/> to indicate that it must set a flag to indicate
				// that its tag has been closed
				$closeTag->setAttribute('set', '');
			}
		}
	}

	/**
	* Optimize an IR
	*
	* @param  DOMDocument $ir
	* @return void
	*/
	protected static function optimize(DOMDocument $ir)
	{
		$xpath = new DOMXPath($ir);

		// Save the output method
		$outputMethod = $ir->documentElement->getAttribute('outputMethod');

		// Get a snapshot of current internal representation
		$xml = $ir->saveXML();

		// Set a maximum number of loops to ward against infinite loops
		$remainingLoops = 10;

		// From now on, keep looping until no further modifications are applied
		do
		{
			$old = $xml;

			// If there's a <closeTag/> right after a <switch/>, clone the <closeTag/> at the end of
			// the every <case/> that does not end with a <closeTag/>
			$query = '//switch[name(following-sibling::*) = "closeTag"]';
			foreach ($xpath->query($query) as $switch)
			{
				$closeTag = $switch->nextSibling;

				foreach ($switch->childNodes as $case)
				{
					if (!$case->lastChild || $case->lastChild->nodeName !== 'closeTag')
					{
						$case->appendChild($closeTag->cloneNode());
					}
				}
			}

			// If there's a <closeTag/> at the beginning of every <case/>, clone it and insert it
			// right before the <switch/> unless there's already one
			$query = '//switch[not(preceding-sibling::closeTag)]';
			foreach ($xpath->query($query) as $switch)
			{
				foreach ($switch->childNodes as $case)
				{
					if (!$case->firstChild || $case->firstChild->nodeName !== 'closeTag')
					{
						// This case is either empty or does not start with a <closeTag/> so we skip
						// to the next <switch/>
						continue 2;
					}
				}

				// Insert the first child of the last <case/>, which should be the same <closeTag/>
				// as every other <case/>
				$switch->parentNode->insertBefore(
					$switch->lastChild->firstChild->cloneNode(),
					$switch
				);
			}

			// If there's a <closeTag/> right after a <switch/>, remove all <closeTag/> nodes at the
			// end of every <case/>
			$query = '//switch[name(following-sibling::*) = "closeTag"]';
			foreach ($xpath->query($query) as $switch)
			{
				foreach ($switch->childNodes as $case)
				{
					while ($case->lastChild && $case->lastChild->nodeName === 'closeTag')
					{
						$case->removeChild($case->lastChild);
					}
				}
			}

			// Finally, for each <closeTag/> remove duplicate <closeTag/> nodes that are either
			// siblings or descendants of a sibling
			$query = '//closeTag';
			foreach ($xpath->query($query) as $closeTag)
			{
				$id    = $closeTag->getAttribute('id');
				$query = 'following-sibling::*/descendant-or-self::closeTag[@id="' . $id . '"]';

				foreach ($xpath->query($query, $closeTag) as $dupe)
				{
					$dupe->parentNode->removeChild($dupe);
				}
			}

			// In HTML mode, a void element cannot have any content
			if ($outputMethod === 'html')
			{
				// For each void element, we find whichever <closeTag/> elements closes it and
				// remove everything after
				foreach ($xpath->query('//element[@void="yes"]') as $element)
				{
					$id    = $element->getAttribute('id');
					$query = './/closeTag[@id="' . $id . '"]/following-sibling::*';

					foreach ($xpath->query($query, $element) as $node)
					{
						$node->parentNode->removeChild($node);
					}
				}
			}

			$xml = $ir->saveXML();
		}
		while (--$remainingLoops > 0 && $xml !== $old);

		// If all branches of a switch have a closeTag we can remove the any closeTag siblings of
		// the switch
		$query = '//switch[not(case[not(closeTag)])]/following-sibling::closeTag';
		foreach ($xpath->query($query) as $closeTag)
		{
			$closeTag->parentNode->removeChild($closeTag);
		}

		// Coalesce consecutive literal outputs
		foreach ($xpath->query('//output[@type="literal"]') as $output)
		{
			while ($output->nextSibling
				&& $output->nextSibling->nodeName === 'output'
				&& $output->nextSibling->getAttribute('type') === 'literal')
			{
				$output->nodeValue
					= htmlspecialchars($output->nodeValue . $output->nextSibling->textContent);
				$output->parentNode->removeChild($output->nextSibling);
			}
		}

		// Remove empty default cases (no @test and no descendants)
		foreach ($xpath->query('//case[not(@test | node())]') as $case)
		{
			$case->parentNode->removeChild($case);
		}
	}

	//==========================================================================
	// Misc
	//==========================================================================

	/**
	* Create and append an element to given node in the IR
	*
	* @param  DOMElement $parentNode Parent node of the element
	* @param  string     $name       Tag name of the element
	* @param  string     $value      Value of the element
	* @return DOMElement             The created element
	*/
	protected static function appendElement(DOMElement $parentNode, $name, $value = '')
	{
		if ($value === '')
		{
			$element = $parentNode->ownerDocument->createElement($name);
		}
		else
		{
			$element = $parentNode->ownerDocument->createElement($name, $value);
		}

		$parentNode->appendChild($element);

		return $element;
	}

	/**
	* Append an <output/> element to given node in the IR
	*
	* @param  DOMElement $ir      Parent node
	* @param  string     $type    Either 'avt', 'literal' or 'xpath'
	* @param  string     $content Content to output
	* @return void
	*/
	protected static function appendOutput(DOMElement $ir, $type, $content)
	{
		// Reparse AVTs and add them as separate xpath/literal outputs
		if ($type === 'avt')
		{
			foreach (TemplateHelper::parseAttributeValueTemplate($content) as $token)
			{
				$type = ($token[0] === 'expression') ? 'xpath' : 'literal';
				self::appendOutput($ir, $type, $token[1]);
			}

			return;
		}

		if ($type === 'xpath')
		{
			// Remove whitespace surrounding XPath expressions
			$content = trim($content);

			// Turn the output into a literal if it's a single string
			if (preg_match('#^(?:"[^"]*"|\'[^\']*\')$#', $content))
			{
				$type    = 'literal';
				$content = substr($content, 1, -1);
			}
		}

		if ($type === 'literal' && $content === '')
		{
			// Don't add empty literals
			return;
		}

		self::appendElement($ir, 'output', htmlspecialchars($content))
			->setAttribute('type', $type);
	}

	/**
	* Test whether given element will be empty at runtime (no content, no children)
	*
	* @param  DOMElement $ir Element in the IR
	* @return string         'yes', 'maybe' or 'no'
	*/
	protected static function isEmpty(DOMElement $ir)
	{
		$xpath = new DOMXPath($ir->ownerDocument);

		// Comments and elements count as not-empty and literal output is sure to output something
		if ($xpath->evaluate('count(comment | element | output[@type="literal"])', $ir))
		{
			return 'no';
		}

		// Test all branches of a <switch/>
		// NOTE: this assumes that <switch/> are normalized to always have a default <case/>
		$cases = [];
		foreach ($xpath->query('switch/case', $ir) as $case)
		{
			$cases[self::isEmpty($case)] = 1;
		}

		if (isset($cases['maybe']))
		{
			return 'maybe';
		}

		if (isset($cases['no']))
		{
			// If all the cases are not-empty, the element is not-empty
			if (!isset($cases['yes']))
			{
				return 'no';
			}

			// Some 'yes' and some 'no', the element is a 'maybe'
			return 'maybe';
		}

		// Test for <apply-templates/> or XPath output
		if ($xpath->evaluate('count(applyTemplates | output[@type="xpath"])', $ir))
		{
			// We can't know in advance whether those will produce output
			return 'maybe';
		}

		return 'yes';
	}
}