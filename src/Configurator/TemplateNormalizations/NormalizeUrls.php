<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMAttr;
use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\AVTHelper;
use s9e\TextFormatter\Configurator\Helpers\NodeLocator;
use s9e\TextFormatter\Parser\AttributeFilters\UrlFilter;
class NormalizeUrls extends AbstractNormalization
{
	protected function getNodes()
	{
		return NodeLocator::getURLNodes($this->ownerDocument);
	}
	protected function normalizeAttribute(DOMAttr $attribute)
	{
		$tokens = AVTHelper::parse(\trim($attribute->value));
		$attrValue = '';
		foreach ($tokens as $_f6b3b659)
		{
			list($type, $content) = $_f6b3b659;
			if ($type === 'literal')
				$attrValue .= UrlFilter::sanitizeUrl($content);
			else
				$attrValue .= '{' . $content . '}';
		}
		$attrValue = $this->unescapeBrackets($attrValue);
		$attribute->value = \htmlspecialchars($attrValue);
	}
	protected function normalizeElement(DOMElement $element)
	{
		$query = './/text()[normalize-space() != ""]';
		foreach ($this->xpath($query, $element) as $i => $node)
		{
			$value = UrlFilter::sanitizeUrl($node->nodeValue);
			if (!$i)
				$value = $this->unescapeBrackets(\ltrim($value));
			$node->nodeValue = $value;
		}
		if (isset($node))
			$node->nodeValue = \rtrim($node->nodeValue);
	}
	protected function unescapeBrackets($url)
	{
		return \preg_replace('#^(\\w+://)%5B([-\\w:._%]+)%5D#i', '$1[$2]', $url);
	}
}