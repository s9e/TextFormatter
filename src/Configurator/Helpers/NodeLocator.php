<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use DOMDocument;
use DOMXPath;

abstract class NodeLocator
{
	/**
	* Return all attributes (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp Regexp
	* @return DOMNode[]           List of DOMNode instances
	*/
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		return self::getNodesByRegexp($dom, $regexp, 'attribute');
	}

	/**
	* Return all DOMNodes whose content is CSS
	*
	* @param  DOMDocument $dom Document
	* @return DOMNode[]        List of DOMNode instances
	*/
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?:color|style)$/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^style$/i')
		);

		return $nodes;
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp Regexp
	* @return DOMNode[]           List of DOMNode instances
	*/
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		return self::getNodesByRegexp($dom, $regexp, 'element');
	}

	/**
	* Return all DOMNodes whose content is JavaScript
	*
	* @param  DOMDocument $dom Document
	* @return DOMNode[]        List of DOMNode instances
	*/
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?:data-s9e-livepreview-)?on/i';
		$nodes  = array_merge(
			self::getAttributesByRegexp($dom, $regexp),
			self::getElementsByRegexp($dom, '/^script$/i')
		);

		return $nodes;
	}

	/**
	* Return all elements (literal or generated) that match given regexp
	*
	* Will return all <param/> descendants of <object/> and all attributes of <embed/> whose name
	* matches given regexp. This method will NOT catch <param/> elements whose 'name' attribute is
	* set via an <xsl:attribute/>
	*
	* @param  DOMDocument $dom    Document
	* @param  string      $regexp
	* @return DOMNode[]           List of DOMNode instances
	*/
	public static function getObjectParamsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Collect attributes from <embed/> elements
		foreach (self::getAttributesByRegexp($dom, $regexp) as $attribute)
		{
			if ($attribute->nodeType === XML_ATTRIBUTE_NODE)
			{
				if (strtolower($attribute->parentNode->localName) === 'embed')
				{
					$nodes[] = $attribute;
				}
			}
			elseif ($xpath->evaluate('count(ancestor::embed)', $attribute))
			{
				// Assuming <xsl:attribute/> or <xsl:copy-of/>
				$nodes[] = $attribute;
			}
		}

		// Collect <param/> descendants of <object/> elements
		foreach ($xpath->query('//object//param') as $param)
		{
			if (preg_match($regexp, $param->getAttribute('name')))
			{
				$nodes[] = $param;
			}
		}

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is an URL
	*
	* NOTE: it will also return HTML4 nodes whose content is an URI
	*
	* @param  DOMDocument $dom Document
	* @return DOMNode[]        List of DOMNode instances
	*/
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?:^(?:action|background|c(?:ite|lassid|odebase)|data|formaction|href|i(?:con|tem(?:id|prop|type))|longdesc|manifest|p(?:ing|luginspage|oster|rofile)|usemap)|src)$/i';
		$nodes  = self::getAttributesByRegexp($dom, $regexp);

		/**
		* @link http://helpx.adobe.com/flash/kb/object-tag-syntax-flash-professional.html
		* @link http://www.sitepoint.com/control-internet-explorer/
		*/
		foreach (self::getObjectParamsByRegexp($dom, '/^(?:dataurl|movie)$/i') as $param)
		{
			$node = $param->getAttributeNode('value');
			if ($node)
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Return all nodes of given type
	*
	* @param  DOMDocument $dom  Owner document
	* @param  string      $type Node type ('element' or 'attribute')
	* @return DOMNode[]         List of DOMNode instances
	*/
	protected static function getNodes(DOMDocument $dom, $type)
	{
		$nodes  = [];
		$prefix = ($type === 'attribute') ? '@' : '';
		$xpath  = new DOMXPath($dom);

		// Get natural nodes
		foreach ($xpath->query('//' . $prefix . '*') as $node)
		{
			$nodes[] = [$node, $node->nodeName];
		}

		// Get XSL-generated nodes
		foreach ($xpath->query('//xsl:' . $type) as $node)
		{
			$nodes[] = [$node, $node->getAttribute('name')];
		}

		// Get xsl:copy-of nodes
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			if (preg_match('/^' . $prefix . '(\\w+)$/', $node->getAttribute('select'), $m))
			{
				$nodes[] = [$node, $m[1]];
			}
		}

		return $nodes;
	}

	/**
	* Return all nodes (literal or generated) that match given regexp
	*
	* @param  DOMDocument $dom    Owner document
	* @param  string      $regexp Regexp
	* @param  string      $type   Node type ('element' or 'attribute')
	* @return DOMNode[]           List of DOMNode instances
	*/
	protected static function getNodesByRegexp(DOMDocument $dom, $regexp, $type)
	{
		$nodes = [];
		foreach (self::getNodes($dom, $type) as list($node, $name))
		{
			if (preg_match($regexp, $name))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}
}