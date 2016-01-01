<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2016 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use DOMXPath;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\TemplateNormalization;
use s9e\TextFormatter\Parser\BuiltInFilters;

class NormalizeUrls extends TemplateNormalization
{
	/**
	* Normalize URLs
	*
	* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		foreach (TemplateHelper::getURLNodes($template->ownerDocument) as $node)
		{
			if ($node instanceof DOMAttr)
			{
				$this->normalizeAttribute($node);
			}
			elseif ($node instanceof DOMElement)
			{
				$this->normalizeElement($node);
			}
		}
	}

	/**
	* Normalize the value of an attribute
	*
	* @param  DOMAttr $attribute
	* @return void
	*/
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		// Trim the URL and parse it
		$tokens = AVTHelper::parse(trim($attribute->value));

		$attrValue = '';
		foreach ($tokens as list($type, $content))
		{
			if ($type === 'literal')
			{
				$attrValue .= BuiltInFilters::sanitizeUrl($content);
			}
			else
			{
				$attrValue .= '{' . $content . '}';
			}
		}

		// Unescape brackets in the host part
		$attrValue = $this->unescapeBrackets($attrValue);

		// Update the attribute's value
		$attribute->value = htmlspecialchars($attrValue);
	}

	/**
	* Normalize value of the text nodes, descendants of an element
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$xpath = new DOMXPath($element->ownerDocument);
		$query = './/text()[normalize-space() != ""]';
		foreach ($xpath->query($query, $element) as $i => $node)
		{
			$value = BuiltInFilters::sanitizeUrl($node->nodeValue);

			if (!$i)
			{
				$value = $this->unescapeBrackets(ltrim($value));
			}

			$node->nodeValue = $value;
		}
		if (isset($node))
		{
			$node->nodeValue = rtrim($node->nodeValue);
		}
	}

	/**
	* Unescape brackets in the host part of a URL if it looks like an IPv6 address
	*
	* @param  string $url
	* @return string
	*/
	protected function unescapeBrackets($url)
	{
		return preg_replace('#^(\\w+://)%5B([-\\w:._%]+)%5D#i', '$1[$2]', $url);
	}
}