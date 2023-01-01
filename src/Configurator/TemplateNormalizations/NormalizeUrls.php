<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMAttr;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\NodeLocator;
use s9e\TextFormatter\Parser\AttributeFilters\UrlFilter;

/**
* @link http://dev.w3.org/html5/spec/links.html#attr-hyperlink-href
*/
class NormalizeUrls extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected function getNodes()
	{
		return NodeLocator::getURLNodes($this->ownerDocument);
	}

	/**
	* {@inheritdoc}
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
				$attrValue .= UrlFilter::sanitizeUrl($content);
			}
			else
			{
				$attrValue .= '{' . $content . '}';
			}
		}

		// Unescape brackets in the host part
		$attrValue = $this->unescapeBrackets($attrValue);

		// Update the attribute's value
		$attribute->value = htmlspecialchars($attrValue, ENT_COMPAT);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		$query = './/text()[normalize-space() != ""]';
		foreach ($this->xpath($query, $element) as $i => $node)
		{
			$value = UrlFilter::sanitizeUrl($node->nodeValue);

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