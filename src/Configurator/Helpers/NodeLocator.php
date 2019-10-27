<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use DOMDocument;
use DOMXPath;
abstract class NodeLocator
{
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		return self::getNodesByRegexp($dom, $regexp, 'attribute');
	}
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?:color|style)$/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);
		return $nodes;
	}
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		return self::getNodesByRegexp($dom, $regexp, 'element');
	}
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?:data-s9e-livepreview-)?on/i';
		$nodes  = \array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);
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
			elseif ($xpath->evaluate('count(ancestor::embed)', $attribute))
				$nodes[] = $attribute;
		foreach ($xpath->query('//object//param') as $param)
			if (\preg_match($regexp, $param->getAttribute('name')))
				$nodes[] = $param;
		return $nodes;
	}
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?:^(?:action|background|c(?:ite|lassid|odebase)|data|formaction|href|i(?:con|tem(?:id|prop|type))|longdesc|manifest|p(?:ing|luginspage|oster|rofile)|usemap)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);
		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
				$nodes[] = $node;
		}
		return $nodes;
	}
	protected static function getNodes(DOMDocument $dom, $type)
	{
		$nodes  = array();
		$prefix = ($type === 'attribute') ? '@' : '';
		$xpath  = new DOMXPath($dom);
		foreach ($xpath->query('//' . $prefix . '*') as $node)
			$nodes[] = array($node, $node->nodeName);
		foreach ($xpath->query('//xsl:' . $type) as $node)
			$nodes[] = array($node, $node->getAttribute('name'));
		foreach ($xpath->query('//xsl:copy-of') as $node)
			if (\preg_match('/^' . $prefix . '(\\w+)$/', $node->getAttribute('select'), $m))
				$nodes[] = array($node, $m[1]);
		return $nodes;
	}
	protected static function getNodesByRegexp(DOMDocument $dom, $regexp, $type)
	{
		$nodes = array();
		foreach (self::getNodes($dom, $type) as $_13697a20)
		{
			list($node, $name) = $_13697a20;
			if (\preg_match($regexp, $name))
				$nodes[] = $node;
		}
		return $nodes;
	}
}