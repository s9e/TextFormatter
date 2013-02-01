<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMNode;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidTemplateException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Items\Tag;

abstract class TemplateHelper
{
	/**
	* Normalize a template to a chunk of optimized, safe XSL
	*
	* @param  string $template Original template
	* @param  Tag    $tag      Tag this template belongs to
	* @return string           Normalized template
	*/
	public static function normalize($template, Tag $tag = null)
	{
		$template = self::normalizeUnsafe($template);
		TemplateChecker::checkUnsafe($template, $tag);

		return $template;
	}

	/**
	* Normalize a template to a chunk of optimized, potentially unsafe XSL
	*
	* @param  string $template Original template
	* @param  Tag    $tag      Tag this template belongs to
	* @return string           Normalized template
	*/
	public static function normalizeUnsafe($template, Tag $tag = null)
	{
		// NOTE: technically, we should start by normalizing the template by loading it with
		//       loadTemplate() but this operation is already done in TemplateOptimizer::optimize()
		//       and there's no practical reason for doing it twice
		$template = TemplateOptimizer::optimize($template);

		return $template;
	}

	/**
	* Attempt to load a template with DOM, first as XML then as HTML as a fallback
	*
	* NOTE: in order to accomodate templates that don't have one single root node, the DOMDocument
	*       returned by this method has its own root node (with a random name) that acts as a parent
	*       to this template's content
	*
	* @param  string      $template
	* @return DOMDocument
	*/
	public static function loadTemplate($template)
	{
		$dom = new DOMDocument;

		// Generate a random tag name so that the user cannot inject stuff outside of that template.
		// For instance, if the tag was <t>, one could input </t><xsl:evil-stuff/><t>
		$t = 't' . sha1(uniqid(mt_rand(), true));

		// First try as XML
		$xml = '<?xml version="1.0" encoding="utf-8" ?><' . $t . ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . $template . '</' . $t . '>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadXML($xml);
		libxml_use_internal_errors($useErrors);

		if ($success)
		{
			// Success!
			return $dom;
		}

		// Couldn't load it as XML... if the template contains an XSL element, abort now, otherwise
		// we'll reparse it as HTML
		if (strpos($template, '<xsl:') !== false)
		{
			$error = libxml_get_last_error();
			throw new InvalidXslException($error->message);
		}

		// Fall back to loading it inside a div, as HTML
		$html = '<html><body><' . $t . '>' . $template . '</' . $t . '></body></html>';

		$useErrors = libxml_use_internal_errors(true);
		$success   = $dom->loadHTML($html);
		libxml_use_internal_errors($useErrors);

		// @codeCoverageIgnoreStart
		if (!$success)
		{
			$error = libxml_get_last_error();
			throw new InvalidTemplateException('Invalid HTML template - error was: ' . $error->message);
		}
		// @codeCoverageIgnoreEnd

		// Now dump the thing as XML and reload it with the proper namespace declaration
		$xml = self::innerXML($dom->getElementsByTagName($t)->item(0));

		return self::loadTemplate($xml);
	}

	/**
	* Serialize a loaded template back into a string
	*
	* NOTE: removes the root node created by loadTemplate()
	*
	* @param  DOMDocument $dom
	* @return string
	*/
	public static function saveTemplate(DOMDocument $dom)
	{
		return self::innerXML($dom->documentElement);
	}

	/**
	* Get the XML content of a node
	*
	* @param  DOMNode $node
	* @return string
	*/
	protected static function innerXML(DOMNode $node)
	{
		// Serialize the XML then remove the outer node
		$xml = $node->ownerDocument->saveXML($node);

		$pos = 1 + strpos($xml, '>');
		$len = strrpos($xml, '<') - $pos;

		// If the template is empty, return an empty string
		if ($len < 1)
		{
			return '';
		}

		$xml = substr($xml, $pos, $len);

		return $xml;
	}

	/**
	* Parse an attribute value template
	*
	* @link http://www.w3.org/TR/xslt#dt-attribute-value-template
	*
	* @param  string $attrValue Attribute value
	* @return array             Array of tokens
	*/
	public static function parseAttributeValueTemplate($attrValue)
	{
		$tokens = [];
		$attrLen = strlen($attrValue);

		$pos = 0;
		while ($pos < $attrLen)
		{
			// Look for opening brackets
			if ($attrValue[$pos] === '{')
			{
				// Two brackets = one literal bracket
				if (substr($attrValue, $pos, 2) === '{{')
				{
					$tokens[] = ['literal', '{'];
					$pos += 2;

					continue;
				}

				// Move the cursor past the left bracket
				++$pos;

				// We're inside an inline XPath expression. We need to parse it in order to find
				// where it ends
				$expr = '';
				while ($pos < $attrLen)
				{
					// Capture everything up to the next "interesting" char: ', " or }
					$spn = strcspn($attrValue, '\'"}', $pos);
					if ($spn)
					{
						$expr .= substr($attrValue, $pos, $spn);
						$pos += $spn;
					}

					if ($pos >= $attrLen)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the character then move the cursor
					$c = $attrValue[$pos];
					++$pos;

					if ($c === '}')
					{
						// Done with this expression
						break;
					}

					// Look for the matching quote
					$quotePos = strpos($attrValue, $c, $pos);
					if ($quotePos === false)
					{
						throw new RuntimeException('Unterminated XPath expression');
					}

					// Capture the content of that string then move the cursor past it
					$expr .= $c . substr($attrValue, $pos, $quotePos + 1 - $pos);
					$pos = 1 + $quotePos;
				}

				$tokens[] = ['expression', $expr];
			}

			$spn = strcspn($attrValue, '{', $pos);
			if ($spn)
			{
				// Capture this chunk of attribute value
				$str = substr($attrValue, $pos, $spn);

				// Unescape right brackets
				$str = str_replace('}}', '}', $str);

				// Add the value and move the cursor
				$tokens[] = ['literal', $str];
				$pos += $spn;
			}
		}

		return $tokens;
	}

	/**
	* Return the list of variables used in a given XPath expression
	*
	* @param  string $expr XPath expression
	* @return array        Alphabetically sorted list of unique variable names
	*/
	public static function getVariablesFromXPath($expr)
	{
		// First, remove strings' contents to prevent false-positives
		$expr = preg_replace('/(["\']).*?\\1/s', '$1$1', $expr);

		// Capture all the variable names
		preg_match_all('/\\$(\\w+)/', $expr, $matches);

		// Dedupe and sort names
		$varNames = array_unique($matches[1]);
		sort($varNames);

		return $varNames;
	}

	/**
	* Return a list of parameters in use in given XSL
	*
	* @param  string $xsl XSL source
	* @return array       Alphabetically sorted list of unique parameter names
	*/
	public static function getParametersFromXSL($xsl)
	{
		$paramNames = array();

		// Wrap the XSL in boilerplate code because it might not have a root element
		$xsl = '<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform">'
		     . '<xsl:template>'
		     . $xsl
		     . '</xsl:template>'
		     . '</xsl:stylesheet>';

		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$xpath = new DOMXPath($dom);

		// Start by collecting XPath expressions in XSL elements
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
		{
			foreach (self::getVariablesFromXPath($attribute->value) as $varName)
			{
				// Test whether this is the name of a local variable
				$varQuery = 'ancestor-or-self::*/'
				          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

				if (!$xpath->query($varQuery, $attribute)->length)
				{
					$paramNames[] = $varName;
				}
			}
		}

		// Collecting XPath expressions in attribute value templates
		$query = '//*[namespace-uri() != "http://www.w3.org/1999/XSL/Transform"]'
		       . '/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$tokens = self::parseAttributeValueTemplate($attribute->value);

			foreach ($tokens as $token)
			{
				if ($token[0] !== 'expression')
				{
					continue;
				}

				foreach (self::getVariablesFromXPath($token[1]) as $varName)
				{
					// Test whether this is the name of a local variable
					$varQuery = 'ancestor-or-self::*/'
					          . 'preceding-sibling::xsl:variable[@name="' . $varName . '"]';

					if (!$xpath->query($varQuery, $attribute)->length)
					{
						$paramNames[] = $varName;
					}
				}
			}
		}

		// Dedupe and sort names
		$paramNames = array_unique($paramNames);
		sort($paramNames);

		return $paramNames;
	}
}