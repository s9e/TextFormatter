<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMAttr;
use DOMCharacterData;
use DOMDocument;
use DOMElement;
use DOMNode;
use DOMProcessingInstruction;
use DOMXPath;
use RuntimeException;
use s9e\TextFormatter\Configurator\Exceptions\InvalidXslException;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

abstract class TemplateHelper
{
	const XMLNS_XSL = 'http://www.w3.org/1999/XSL/Transform';

	public static function loadTemplate($template)
	{
		$dom = new DOMDocument;

		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $template . '</xsl:template>';

		$useErrors = \libxml_use_internal_errors(\true);
		$success   = $dom->loadXML($xml);
		\libxml_use_internal_errors($useErrors);

		if ($success)
			return $dom;

		$tmp = \preg_replace('(&(?![A-Za-z0-9]+;|#\\d+;|#x[A-Fa-f0-9]+;))', '&amp;', $template);
		$tmp = \preg_replace_callback(
			'(&(?!quot;|amp;|apos;|lt;|gt;)\\w+;)',
			function ($m)
			{
				return \html_entity_decode($m[0], \ENT_NOQUOTES, 'UTF-8');
			},
			$tmp
		);
		$xml = '<?xml version="1.0" encoding="utf-8" ?><xsl:template xmlns:xsl="' . self::XMLNS_XSL . '">' . $tmp . '</xsl:template>';

		$useErrors = \libxml_use_internal_errors(\true);
		$success   = $dom->loadXML($xml);
		\libxml_use_internal_errors($useErrors);

		if ($success)
			return $dom;

		if (\strpos($template, '<xsl:') !== \false)
		{
			$error = \libxml_get_last_error();
			throw new InvalidXslException($error->message);
		}

		$html = '<html><body><div>' . $template . '</div></body></html>';

		$useErrors = \libxml_use_internal_errors(\true);
		$dom->loadHTML($html);
		\libxml_use_internal_errors($useErrors);

		$xml = self::innerXML($dom->documentElement->firstChild->firstChild);

		return self::loadTemplate($xml);
	}

	public static function saveTemplate(DOMDocument $dom)
	{
		return self::innerXML($dom->documentElement);
	}

	protected static function innerXML(DOMElement $element)
	{
		$xml = $element->ownerDocument->saveXML($element);

		$pos = 1 + \strpos($xml, '>');
		$len = \strrpos($xml, '<') - $pos;

		if ($len < 1)
			return '';

		$xml = \substr($xml, $pos, $len);

		return $xml;
	}

	public static function getParametersFromXSL($xsl)
	{
		$paramNames = array();

		$xsl = '<xsl:stylesheet xmlns:xsl="' . self::XMLNS_XSL . '"><xsl:template>'
		     . $xsl
		     . '</xsl:template></xsl:stylesheet>';

		$dom = new DOMDocument;
		$dom->loadXML($xsl);

		$xpath = new DOMXPath($dom);

		$query = '//xsl:*/@match | //xsl:*/@select | //xsl:*/@test';
		foreach ($xpath->query($query) as $attribute)
			foreach (XPathHelper::getVariables($attribute->value) as $varName)
			{
				$varQuery = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $varName . '"]';

				if (!$xpath->query($varQuery, $attribute)->length)
					$paramNames[] = $varName;
			}

		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*[contains(., "{")]';
		foreach ($xpath->query($query) as $attribute)
		{
			$tokens = AVTHelper::parse($attribute->value);

			foreach ($tokens as $token)
			{
				if ($token[0] !== 'expression')
					continue;

				foreach (XPathHelper::getVariables($token[1]) as $varName)
				{
					$varQuery = 'ancestor-or-self::*/preceding-sibling::xsl:variable[@name="' . $varName . '"]';

					if (!$xpath->query($varQuery, $attribute)->length)
						$paramNames[] = $varName;
				}
			}
		}

		$paramNames = \array_unique($paramNames);
		\sort($paramNames);

		return $paramNames;
	}

	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = array();

		foreach ($xpath->query('//@*') as $attribute)
			if (\preg_match($regexp, $attribute->name))
				$nodes[] = $attribute;

		foreach ($xpath->query('//xsl:attribute') as $attribute)
			if (\preg_match($regexp, $attribute->getAttribute('name')))
				$nodes[] = $attribute;

		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (\preg_match('/^@(\\w+)$/', $expr, $m)
			 && \preg_match($regexp, $m[1]))
				$nodes[] = $node;
		}

		return $nodes;
	}

	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = array();

		foreach ($xpath->query('//*') as $element)
			if (\preg_match($regexp, $element->localName))
				$nodes[] = $element;

		foreach ($xpath->query('//xsl:element') as $element)
			if (\preg_match($regexp, $element->getAttribute('name')))
				$nodes[] = $element;

		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');

			if (\preg_match('/^\\w+$/', $expr)
			 && \preg_match($regexp, $expr))
				$nodes[] = $node;
		}

		return $nodes;
	}

	public static function getObjectParamsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = array();

		foreach (self::getAttributesByRegexp($dom, $regexp) as $attribute)
			if ($attribute->nodeType === \XML_ATTRIBUTE_NODE)
			{
				if (\strtolower($attribute->parentNode->localName) === 'embed')
					$nodes[] = $attribute;
			}
			elseif ($xpath->evaluate('ancestor::embed', $attribute))
				$nodes[] = $attribute;

		foreach ($dom->getElementsByTagName('object') as $object)
			foreach ($object->getElementsByTagName('param') as $param)
				if (\preg_match($regexp, $param->getAttribute('name')))
					$nodes[] = $param;

		return $nodes;
	}

	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^style$/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);

		return $nodes;
	}

	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?>data-s9e-livepreview-postprocess$|on)/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);

		return $nodes;
	}

	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?>^(?>action|background|c(?>ite|lassid|odebase)|data|formaction|href|icon|longdesc|manifest|p(?>luginspage|oster|rofile)|usemap)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);

		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
				$nodes[] = $node;
		}

		return $nodes;
	}

	public static function replaceTokens($template, $regexp, $fn)
	{
		if ($template === '')
			return $template;

		$dom   = self::loadTemplate($template);
		$xpath = new DOMXPath($dom);

		foreach ($xpath->query('//@*') as $attribute)
		{
			$attrValue = \preg_replace_callback(
				$regexp,
				function ($m) use ($fn, $attribute)
				{
					$replacement = $fn($m, $attribute);

					if ($replacement[0] === 'expression')
						return '{' . $replacement[1] . '}';
					elseif ($replacement[0] === 'passthrough')
						return ($replacement[1]) ? '{.}' : '{substring(.,1+string-length(st),string-length()-(string-length(st)+string-length(et)))}';
					else
						return $replacement[1];
				},
				$attribute->value
			);

			$attribute->value = \htmlspecialchars($attrValue, \ENT_COMPAT, 'UTF-8');
		}

		foreach ($xpath->query('//text()') as $node)
		{
			\preg_match_all(
				$regexp,
				$node->textContent,
				$matches,
				\PREG_SET_ORDER | \PREG_OFFSET_CAPTURE
			);

			if (empty($matches))
				continue;

			$parentNode = $node->parentNode;

			$lastPos = 0;
			foreach ($matches as $m)
			{
				$pos = $m[0][1];

				if ($pos > $lastPos)
					$parentNode->insertBefore(
						$dom->createTextNode(
							\substr($node->textContent, $lastPos, $pos - $lastPos)
						),
						$node
					);
				$lastPos = $pos + \strlen($m[0][0]);

				$_m = array();
				foreach ($m as $capture)
					$_m[] = $capture[0];

				$replacement = $fn($_m, $node);

				if ($replacement[0] === 'expression')
					$parentNode
						->insertBefore(
							$dom->createElementNS(self::XMLNS_XSL, 'xsl:value-of'),
							$node
						)
						->setAttribute('select', $replacement[1]);
				elseif ($replacement[0] === 'passthrough')
					$parentNode->insertBefore(
						$dom->createElementNS(self::XMLNS_XSL, 'xsl:apply-templates'),
						$node
					);
				else
					$parentNode->insertBefore($dom->createTextNode($replacement[1]), $node);
			}

			$text = \substr($node->textContent, $lastPos);
			if ($text > '')
				$parentNode->insertBefore($dom->createTextNode($text), $node);

			$parentNode->removeChild($node);
		}

		return self::saveTemplate($dom);
	}

	public static function highlightNode(DOMNode $node, $prepend, $append)
	{
		$uniqid = \uniqid('_');
		if ($node instanceof DOMAttr)
			$node->value .= $uniqid;
		elseif ($node instanceof DOMElement)
			$node->setAttribute($uniqid, '');
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
			$node->data .= $uniqid;

		$dom = $node->ownerDocument;
		$dom->formatOutput = \true;

		$docXml = self::innerXML($dom->documentElement);
		$docXml = \trim(\str_replace("\n  ", "\n", $docXml));

		$nodeHtml = \htmlspecialchars(\trim($dom->saveXML($node)));
		$docHtml  = \htmlspecialchars($docXml);

		$html = \str_replace($nodeHtml, $prepend . $nodeHtml . $append, $docHtml);

		if ($node instanceof DOMAttr)
		{
			$node->value = \substr($node->value, 0, -\strlen($uniqid));
			$html = \str_replace($uniqid, '', $html);
		}
		elseif ($node instanceof DOMElement)
		{
			$node->removeAttribute($uniqid);
			$html = \str_replace(' ' . $uniqid . '=&quot;&quot;', '', $html);
		}
		elseif ($node instanceof DOMCharacterData
		     || $node instanceof DOMProcessingInstruction)
		{
			$node->data .= $uniqid;
			$html = \str_replace($uniqid, '', $html);
		}

		return $html;
	}

	public static function getMetaElementsRegexp(array $templates)
	{
		$exprs = array();

		$xsl = '<xsl:template xmlns:xsl="http://www.w3.org/1999/XSL/Transform">' . \implode('', $templates) . '</xsl:template>';
		$dom = new DOMDocument;
		$dom->loadXML($xsl);
		$xpath = new DOMXPath($dom);

		$query = '//xsl:*/@*[contains("matchselectest", name())]';
		foreach ($xpath->query($query) as $attribute)
			$exprs[] = $attribute->value;

		$query = '//*[namespace-uri() != "' . self::XMLNS_XSL . '"]/@*';
		foreach ($xpath->query($query) as $attribute)
			foreach (AVTHelper::parse($attribute->value) as $token)
				if ($token[0] === 'expression')
					$exprs[] = $token[1];

		$tagNames = array(
			'e' => \true,
			'i' => \true,
			's' => \true
		);

		foreach (\array_keys($tagNames) as $tagName)
			if (isset($templates[$tagName]) && $templates[$tagName] !== '')
				unset($tagNames[$tagName]);

		$regexp = '(\\b(?<![$@])(' . \implode('|', \array_keys($tagNames)) . ')(?!-)\\b)';

		\preg_match_all($regexp, \implode("\n", $exprs), $m);

		foreach ($m[0] as $tagName)
			unset($tagNames[$tagName]);

		if (empty($tagNames))
			return '((?!))';

		return '(<' . RegexpBuilder::fromList(\array_keys($tagNames)) . '>[^<]*</[^>]+>)';
	}

	public static function replaceHomogeneousTemplates(array &$templates, $minCount = 3)
	{
		$tagNames = array();

		$expr = 'name()';

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

		if (\is_string($chars) && $chars !== '')
			$expr = 'translate(' . $expr . ",'" . $chars . "','" . \strtolower($chars) . "')";

		$template = '<xsl:element name="{' . $expr . '}"><xsl:apply-templates/></xsl:element>';

		foreach ($tagNames as $tagName)
			$templates[$tagName] = $template;
	}
}