<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMAttr;
use DOMDocument;
use DOMText;
use DOMXPath;
abstract class TemplateModifier
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static function replaceTokens($template, $regexp, $fn)
	{
		$dom   = TemplateLoader::load($template);
		$xpath = new DOMXPath($dom);
		foreach ($xpath->query('//@*') as $attribute)
			self::replaceTokensInAttribute($attribute, $regexp, $fn);
		foreach ($xpath->query('//text()') as $node)
			self::replaceTokensInText($node, $regexp, $fn);
		return TemplateLoader::save($dom);
	}
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
				$newNode->setAttribute('select', $replacement[1]);
		}
		else
			$newNode = $dom->createTextNode($replacement[1]);
		return $newNode;
	}
	protected static function replaceTokensInAttribute(DOMAttr $attribute, $regexp, $fn)
	{
		$attrValue = \preg_replace_callback(
			$regexp,
			function ($m) use ($fn, $attribute)
			{
				$replacement = $fn($m, $attribute);
				if ($replacement[0] === 'expression' || $replacement[0] === 'passthrough')
				{
					$replacement[] = '.';
					return '{' . $replacement[1] . '}';
				}
				else
					return $replacement[1];
			},
			$attribute->value
		);
		$attribute->value = \htmlspecialchars($attrValue, \ENT_COMPAT, 'UTF-8');
	}
	protected static function replaceTokensInText(DOMText $node, $regexp, $fn)
	{
		$parentNode = $node->parentNode;
		$dom        = $node->ownerDocument;
		\preg_match_all($regexp, $node->textContent, $matches, \PREG_SET_ORDER | \PREG_OFFSET_CAPTURE);
		$lastPos = 0;
		foreach ($matches as $m)
		{
			$pos = $m[0][1];
			$text = \substr($node->textContent, $lastPos, $pos - $lastPos);
			$parentNode->insertBefore($dom->createTextNode($text), $node);
			$lastPos = $pos + \strlen($m[0][0]);
			$_m=[];foreach($m as $v)$_m[]=$v[0];$replacement = $fn($_m, $node);
			$newNode     = self::createReplacementNode($dom, $replacement);
			$parentNode->insertBefore($newNode, $node);
		}
		$text = \substr($node->textContent, $lastPos);
		$parentNode->insertBefore($dom->createTextNode($text), $node);
		$parentNode->removeChild($node);
	}
}