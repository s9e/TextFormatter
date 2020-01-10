<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;

abstract class TemplateLoader
{
	/**
	* XSL namespace
	*/
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	/**
	* Get the XML content of an element
	*
	* @private
	*
	* @param  DOMElement $element
	* @return string
	*/
	public static function innerXML(DOMElement $element)
	{
		// Serialize the XML then remove the outer element
		$xml = $element->ownerDocument->saveXML($element);
		$pos = 1 + strpos($xml, '>');
		$len = strrpos($xml, '<') - $pos;

		// If the template is empty, return an empty string
		return ($len < 1) ? '' : substr($xml, $pos, $len);
	}

	/**
	* Load a template as an xsl:template node
	*
	* Will attempt to load it as XML first, then as HTML as a fallback. Either way, an xsl:template
	* node is returned
	*
	* @param  string      $template
	* @return DOMDocument
	*/
	public static function load($template)
	{
		$dom = self::loadAsXML($template) ?: self::loadAsXML(self::fixEntities($template));
		if ($dom)
		{
			return $dom;
		}

		// If the template contains an XSL element, abort now. Otherwise, try reparsing it as HTML
		if (strpos($template, '<xsl:') !== false)
		{
			$error = libxml_get_last_error();

			throw new RuntimeException('Invalid XSL: ' . $error->message);
		}

		return self::loadAsHTML($template);
	}

	/**
	* Serialize a loaded template back into a string
	*
	* NOTE: removes the root node created by load()
	*
	* @param  DOMDocument $dom
	* @return string
	*/
	public static function save(DOMDocument $dom)
	{
		$xml = self::innerXML($dom->documentElement);
		if (strpos($xml, 'xmlns:xsl') !== false)
		{
			$xml = preg_replace('((<[^>]+?) xmlns:xsl="' . self::XMLNS_XSL . '")', '$1', $xml);
		}

		return $xml;
	}

	/**
	* Replace HTML entities and unescaped ampersands in given template
	*
	* @param  string $template
	* @return string
	*/
	protected static function fixEntities($template)
	{
		$template = self::replaceEntities($template);
		$template = preg_replace('(&(?!quot;|amp;|apos;|lt;|gt;|#\\d+;|#x[A-Fa-f0-9]+;))', '&amp;', $template);

		return $template;
	}

	/**
	* Load given HTML template in a DOM document
	*
	* @param  string      $template Original template
	* @return DOMDocument
	*/
	protected static function loadAsHTML($template)
	{
		$template = self::replaceCDATA($template);
		$template = self::replaceEntities($template);

		$dom  = new DOMDocument;
		$html = '<?xml version="1.0" encoding="utf-8" ?><html><body><div>' . $template . '</div></body></html>';

		$useErrors = libxml_use_internal_errors(true);
		$dom->loadHTML($html, LIBXML_NSCLEAN);
		self::removeInvalidAttributes($dom);
		libxml_use_internal_errors($useErrors);

		// Now dump the thing as XML then reload it with the proper root element
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . self::innerXML($dom->documentElement->firstChild->firstChild) . '</xsl:template>';

		$useErrors = libxml_use_internal_errors(true);
		$dom->loadXML($xml, LIBXML_NSCLEAN);
		libxml_use_internal_errors($useErrors);

		return $dom;
	}

	/**
	* Load given XSL template in a DOM document
	*
	* @param  string           $template Original template
	* @return bool|DOMDocument           DOMDocument on success, FALSE otherwise
	*/
	protected static function loadAsXML($template)
	{
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';

		$useErrors = libxml_use_internal_errors(true);
		$dom       = new DOMDocument;
		$success   = $dom->loadXML($xml, LIBXML_NOCDATA | LIBXML_NSCLEAN);
		self::removeInvalidAttributes($dom);
		libxml_use_internal_errors($useErrors);

		return ($success) ? $dom : false;
	}

	/**
	* Remove attributes with an invalid name from given DOM document
	*
	* @param  DOMDocument $dom
	* @return void
	*/
	protected static function removeInvalidAttributes(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attribute)
		{
			if (!preg_match('(^(?:[-\\w]+:)?(?!\\d)[-\\w]+$)D', $attribute->nodeName))
			{
				$attribute->parentNode->removeAttributeNode($attribute);
			}
		}
	}

	/**
	* Replace CDATA sections in given template
	*
	* @param  string $template Original template
	* @return string           Modified template
	*/
	protected static function replaceCDATA($template)
	{
		return preg_replace_callback(
			'(<!\\[CDATA\\[(.*?)\\]\\]>)',
			function ($m)
			{
				return htmlspecialchars($m[1]);
			},
			$template
		);
	}

	/**
	* Replace known HTML entities
	*
	* @param  string $template
	* @return string
	*/
	protected static function replaceEntities(string $template): string
	{
		return preg_replace_callback(
			'(&(?!quot;|amp;|apos;|lt;|gt;)\\w+;)',
			function ($m)
			{
				return html_entity_decode($m[0], ENT_HTML5 | ENT_NOQUOTES, 'UTF-8');
			},
			str_replace('&AMP;', '&amp;', $template)
		);
	}
}