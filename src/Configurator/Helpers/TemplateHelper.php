<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMNode;
use Exception;
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
}