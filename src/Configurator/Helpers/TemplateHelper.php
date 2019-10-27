<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMText;
use DOMXPath;
abstract class TemplateHelper
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';
	public static function getParametersFromXSL($xsl)
	{
		$paramNames = array();
		$xpath      = new DOMXPath(TemplateLoader::load($xsl));
		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
		{
			$expr        = $attribute->value;
			$paramNames += \array_flip(self::getParametersFromExpression($attribute, $expr));
		}
		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
			foreach (AVTHelper::parse($attribute->value) as $token)
				if ($token[0] === 'expression')
				{
					$expr        = $token[1];
					$paramNames += \array_flip(self::getParametersFromExpression($attribute, $expr));
				}
		\ksort($paramNames);
		return \array_keys($paramNames);
	}
	public static function highlightNode(DOMNode $node, $prepend, $append)
	{
		$dom = $node->ownerDocument->cloneNode(\true);
		$dom->formatOutput = \true;
		$xpath = new DOMXPath($dom);
		$node  = $xpath->query($node->getNodePath())->item(0);
		$uniqid = \uniqid('_');
		if ($node instanceof DOMAttr)
			$node->value .= $uniqid;
		elseif ($node instanceof DOMElement)
			$node->setAttribute($uniqid, '');
		elseif ($node instanceof DOMCharacterData || $node instanceof DOMProcessingInstruction)
			$node->data .= $uniqid;
		$docXml = TemplateLoader::innerXML($dom->documentElement);
		$docXml = \trim(\str_replace("\n  ", "\n", $docXml));
		$nodeHtml = \htmlspecialchars(\trim($dom->saveXML($node)));
		$docHtml  = \htmlspecialchars($docXml);
		$html = \str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);
		$html = \str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		$html = \str_replace($uniqid, '', $html);
		return $html;
	}
	public static function replaceHomogeneousTemplates(array &$templates, $minCount = 3)
	{
		$expr = 'name()';
		$tagNames = array();
		foreach ($templates as $tagName => $template)
		{
			$elName = \strtolower(\preg_replace('/^[^:]+:/', '', $tagName));
			if ($template === '<' . $elName . '><xsl:apply-templates/></' . $elName . '>')
			{
				$tagNames[] = $tagName;
				if (\strpos($tagName, ':') !== \false)
					$expr = 'local-name()';
			}
		}
		if (\count($tagNames) < $minCount)
			return;
		$chars = \preg_replace('/[^A-Z]+/', '', \count_chars(\implode('', $tagNames), 3));
		if ($chars > '')
			$expr = 'translate(' . $expr . ",'" . $chars . "','" . \strtolower($chars) . "')";
		$template = '<xsl:element name="{' . $expr . '}"><xsl:apply-templates/></xsl:element>';
		foreach ($tagNames as $tagName)
			$templates[$tagName] = $template;
	}
	protected static function getParametersFromExpression(DOMNode $node, $expr)
	{
		$varNames   = XPathHelper::getVariables($expr);
		$paramNames = array();
		$xpath      = new DOMXPath($node->ownerDocument);
		foreach ($varNames as $name)
		{
			$query = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $name . '"]';
			if (!$xpath->query($query, $node)->length)
				$paramNames[] = $name;
		}
		return $paramNames;
	}
}