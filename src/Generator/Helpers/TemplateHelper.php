<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator\Helpers;

use DOMDocument;
use Exception;
use s9e\TextFormatter\Generator\Exceptions\InvalidTemplateException;
use s9e\TextFormatter\Generator\Items\Tag;

abstract class TemplateHelper
{
	/**
	* 
	*
	* @return void
	*/
	public function normalize($template, Tag $tag = null)
	{
		if (!isset($tag))
		{
			$tag = new Tag;
		}

		$dom = self::loadTemplate($template);

		$template = TemplateOptimizer::optimize($template);
		TemplateChecker::checkUnsafe($template, $tag);

		return $template;
	}

	/**
	* Attempt to load a template with DOM, first as XML then as HTML as a fallback
	*
	* @param  string      $template
	* @return DOMDocument
	*/
	public static function loadTemplate($template)
	{
		$dom = new DOMDocument;

		// Generate a random tag name so that the user cannot inject stuff outside of that template.
		// For instance, if the tag was <t>, one could input </t><xsl:evil-stuff/><t>
		$t = 't' . md5(microtime(true) . mt_rand());

		// First try as XML
		$xml = '<?xml version="1.0" encoding="utf-8" ?><' . $t . ' xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . $template . '</' . $t . '>';

		try
		{
			$useErrors = libxml_use_internal_errors(true);
			$success = $dom->loadXML($xml);
		}
		catch (Exception $e)
		{
		}

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
			throw new InvalidTemplateException('Invalid template - error was: ' . $error->message);
		}

		// Fall back to loading it inside a div, as HTML
		$html = '<html><body><div id="' . $t . '">' . $template . '</div></body></html>';

		$useErrors = libxml_use_internal_errors(true);
		$success = $dom->loadHTML($html);
		libxml_use_internal_errors($useErrors);

		// @codeCoverageIgnoreStart
		if (!$success)
		{
			$error = libxml_get_last_error();
			throw new InvalidTemplateException('Invalid HTML in template - error was: ' . $error->message);
		}
		// @codeCoverageIgnoreEnd

		// Now dump the thing as XML and reload it to ensure we don't have to worry about internal
		// shenanigans
		$xml = $dom->saveXML($dom->getElementById($t));

		$dom = new DOMDocument;
		$dom->loadXML($xml);

		return $dom;
	}
}