<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMDocument;
use DOMElement;
use DOMXPath;
use RuntimeException;
abstract class TemplateLoader
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static function innerXML(DOMElement $element)
	{
		$xml = $element->ownerDocument->saveXML($element);
		$pos = 1 + \strpos($xml, '>');
		$len = \strrpos($xml, '<') - $pos;
		return ($len < 1) ? '' : \substr($xml, $pos, $len);
	}
	public static function load($template)
	{
		$dom = self::loadAsXML($template) ?: self::loadAsXML(self::fixEntities($template));
		if ($dom)
			return $dom;
		if (\strpos($template, '<xsl:') !== \false)
		{
			$error = \libxml_get_last_error();
			throw new RuntimeException('Invalid XSL: ' . $error->message);
		}
		return self::loadAsHTML($template);
	}
	public static function save(DOMDocument $dom)
	{
		$xml = self::innerXML($dom->documentElement);
		if (\strpos($xml, 'xmlns:xsl') !== \false)
			$xml = \preg_replace('((<[^>]+?) xmlns:xsl="' . self::XMLNS_XSL . '")', '$1', $xml);
		return $xml;
	}
	protected static function fixEntities($template)
	{
		return \preg_replace_callback(
			'(&(?!quot;|amp;|apos;|lt;|gt;)\\w+;)',
			function ($m)
			{
				return \html_entity_decode($m[0], \ENT_NOQUOTES, 'UTF-8');
			},
			\preg_replace('(&(?![A-Za-z0-9]+;|#\\d+;|#x[A-Fa-f0-9]+;))', '&amp;', $template)
		);
	}
	protected static function loadAsHTML($template)
	{
		$template = self::replaceCDATA($template);
		$dom  = new DOMDocument;
		$html = '<?xml version="1.0" encoding="utf-8" ?><html><body><div>' . $template . '</div></body></html>';
		$useErrors = \libxml_use_internal_errors(\true);
		$dom->loadHTML($html, \LIBXML_NSCLEAN);
		self::removeInvalidAttributes($dom);
		\libxml_use_internal_errors($useErrors);
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . self::innerXML($dom->documentElement->firstChild->firstChild) . '</xsl:template>';
		$useErrors = \libxml_use_internal_errors(\true);
		$dom->loadXML($xml, \LIBXML_NSCLEAN);
		\libxml_use_internal_errors($useErrors);
		return $dom;
	}
	protected static function loadAsXML($template)
	{
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';
		$useErrors = \libxml_use_internal_errors(\true);
		$dom       = new DOMDocument;
		$success   = $dom->loadXML($xml, \LIBXML_NOCDATA | \LIBXML_NSCLEAN);
		self::removeInvalidAttributes($dom);
		\libxml_use_internal_errors($useErrors);
		return ($success) ? $dom : \false;
	}
	protected static function removeInvalidAttributes(DOMDocument $dom)
	{
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attribute)
			if (!\preg_match('(^(?:[-\\w]+:)?(?!\\d)[-\\w]+$)D', $attribute->nodeName))
				$attribute->parentNode->removeAttributeNode($attribute);
	}
	protected static function replaceCDATA($template)
	{
		return \preg_replace_callback(
			'(<!\\[CDATA\\[(.*?)\\]\\]>)',
			function ($m)
			{
				return \htmlspecialchars($m[1]);
			},
			$template
		);
	}
}