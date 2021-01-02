<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMDocument;
use DOMText;
use DOMXPath;

abstract class TemplateModifier
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* Replace parts of a template that match given regexp
	*
	* Treats attribute values as plain text. Replacements within XPath expression is unsupported.
	* The callback must return an array with two elements. The first must be either of 'expression',
	* 'literal' or 'passthrough', and the second element depends on the first.
	*
	*  - 'expression' indicates that the replacement must be treated as an XPath expression such as
	*    '@foo', which must be passed as the second element.
	*
	*  - 'literal' indicates a literal (plain text) replacement, passed as its second element.
	*
	*  - 'passthrough' indicates that the replacement should the tag's content. It works differently
	*    whether it is inside an attribute's value or a text node. Within an attribute's value, the
	*    replacement will be the text content of the tag. Within a text node, the replacement
	*    becomes an <xsl:apply-templates/> node. A second optional argument can be passed to be used
	*    as its @select node-set.
	*
	* @param  string   $template Original template
	* @param  string   $regexp   Regexp for matching parts that need replacement
	* @param  callback $fn       Callback used to get the replacement
	* @return string             Processed template
	*/
	public static function replaceTokens($template, $regexp, $fn)
	{
		$dom   = TemplateLoader::load($template);
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attribute)
		{
			self::replaceTokensInAttribute($attribute, $regexp, $fn);
		}
		foreach ($xpath->query('//text()') as $node)
		{
			self::replaceTokensInText($node, $regexp, $fn);
		}

		return TemplateLoader::save($dom);
	}

	/**
	* Create a node that implements given replacement strategy
	*
	* @param  DOMDocument $dom
	* @param  array       $replacement
	* @return DOMNode
	*/
	protected static function createReplacementNode(DOMDocument $dom, array $replacement)
	{
		if ($replacement[0] === 'expression')
		{
			$newNode = $dom->createElementNS(self::XMLNS_XSL, 'xsl:value-of');
			$newNode->setAttribute('select', $replacement[1]);
		}
		elseif ($replacement[0] === 'passthrough')
		{
			$newNode = $dom->createElementNS(self::XMLNS_XSL, 'xsl:apply-templates');
			if (isset($replacement[1]))
			{
				$newNode->setAttribute('select', $replacement[1]);
			}
		}
		else
		{
			$newNode = $dom->createTextNode($replacement[1]);
		}

		return $newNode;
	}

	/**
	* Replace parts of an attribute that match given regexp
	*
	* @param  DOMAttr  $attribute Attribute
	* @param  string   $regexp    Regexp for matching parts that need replacement
	* @param  callback $fn        Callback used to get the replacement
	* @return void
	*/
	protected static function replaceTokensInAttribute(DOMAttr $attribute, $regexp, $fn)
	{
		$attrValue = preg_replace_callback(
			$regexp,
			function ($m) use ($fn, $attribute)
			{
				$replacement = $fn($m, $attribute);
				if ($replacement[0] === 'expression' || $replacement[0] === 'passthrough')
				{
					// Use the node's text content as the default expression
					$replacement[] = '.';

					return '{' . $replacement[1] . '}';
				}
				else
				{
					return $replacement[1];
				}
			},
			$attribute->value
		);
		$attribute->value = htmlspecialchars($attrValue, ENT_COMPAT, 'UTF-8');
	}

	/**
	* Replace parts of a text node that match given regexp
	*
	* @param  DOMText  $node   Text node
	* @param  string   $regexp Regexp for matching parts that need replacement
	* @param  callback $fn     Callback used to get the replacement
	* @return void
	*/
	protected static function replaceTokensInText(DOMText $node, $regexp, $fn)
	{
		// Grab the node's parent so that we can rebuild the text with added variables right
		// before the node, using DOM's insertBefore(). Technically, it would make more sense
		// to create a document fragment, append nodes then replace the node with the fragment
		// but it leads to namespace redeclarations, which looks ugly
		$parentNode = $node->parentNode;
		$dom        = $node->ownerDocument;

		preg_match_all($regexp, $node->textContent, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE);
		$lastPos = 0;
		foreach ($matches as $m)
		{
			$pos = $m[0][1];

			// Catch-up to current position
			$text = substr($node->textContent, $lastPos, $pos - $lastPos);
			$parentNode->insertBefore($dom->createTextNode($text), $node);
			$lastPos = $pos + strlen($m[0][0]);

			// Get the replacement for this token
			$replacement = $fn(array_column($m, 0), $node);
			$newNode     = self::createReplacementNode($dom, $replacement);
			$parentNode->insertBefore($newNode, $node);
		}

		// Append the rest of the text
		$text = substr($node->textContent, $lastPos);
		$parentNode->insertBefore($dom->createTextNode($text), $node);

		// Now remove the old text node
		$parentNode->removeChild($node);
	}
}