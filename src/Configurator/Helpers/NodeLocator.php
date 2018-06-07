<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2018 The s9e Authors
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
	* @return array               Array of DOMNode instances
	*/
	public static function getAttributesByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//@*') as $attribute)
		{
			if (preg_match($regexp, $attribute->name))
			{
				$nodes[] = $attribute;
			}
		}

		// Get generated attributes
		foreach ($xpath->query('//xsl:attribute') as $attribute)
		{
			if (preg_match($regexp, $attribute->getAttribute('name')))
			{
				$nodes[] = $attribute;
			}
		}

		// Get attributes created with <xsl:copy-of/>
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');
			if (preg_match('/^@(\\w+)$/', $expr, $m) && preg_match($regexp, $m[1]))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is CSS
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getCSSNodes(DOMDocument $dom)
	{
		$regexp = '/^style$/i';
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
	* @return array               Array of DOMNode instances
	*/
	public static function getElementsByRegexp(DOMDocument $dom, $regexp)
	{
		$xpath = new DOMXPath($dom);
		$nodes = [];

		// Get literal attributes
		foreach ($xpath->query('//*') as $element)
		{
			if (preg_match($regexp, $element->localName))
			{
				$nodes[] = $element;
			}
		}

		// Get generated elements
		foreach ($xpath->query('//xsl:element') as $element)
		{
			if (preg_match($regexp, $element->getAttribute('name')))
			{
				$nodes[] = $element;
			}
		}

		// Get elements created with <xsl:copy-of/>
		// NOTE: this method of creating elements is disallowed by default
		foreach ($xpath->query('//xsl:copy-of') as $node)
		{
			$expr = $node->getAttribute('select');
			if (preg_match('/^\\w+$/', $expr) && preg_match($regexp, $expr))
			{
				$nodes[] = $node;
			}
		}

		return $nodes;
	}

	/**
	* Return all DOMNodes whose content is JavaScript
	*
	* @param  DOMDocument $dom Document
	* @return array            Array of DOMNode instances
	*/
	public static function getJSNodes(DOMDocument $dom)
	{
		$regexp = '/^(?:data-s9e-livepreview-postprocess$|on)/i';
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
	* @return array               Array of DOMNode instances
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
			elseif ($xpath->evaluate('ancestor::embed', $attribute))
			{
				// Assuming <xsl:attribute/> or <xsl:copy-of/>
				$nodes[] = $attribute;
			}
		}

		// Collect <param/> descendants of <object/> elements
		foreach ($dom->getElementsByTagName('object') as $object)
		{
			foreach ($object->getElementsByTagName('param') as $param)
			{
				if (preg_match($regexp, $param->getAttribute('name')))
				{
					$nodes[] = $param;
				}
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
	* @return array            Array of DOMNode instances
	*/
	public static function getURLNodes(DOMDocument $dom)
	{
		$regexp = '/(?:^(?:action|background|c(?:ite|lassid|odebase)|data|formaction|href|icon|longdesc|manifest|p(?:ing|luginspage|oster|rofile)|usemap)|src)$/i';
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
}