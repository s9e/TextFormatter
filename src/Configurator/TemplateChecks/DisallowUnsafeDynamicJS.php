<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2022 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\NodeLocator;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;

class DisallowUnsafeDynamicJS extends AbstractDynamicContentCheck
{
	/**
	* {@inheritdoc}
	*/
	protected function getNodes(DOMElement $template)
	{
		return NodeLocator::getJSNodes($template->ownerDocument);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isExpressionSafe($expr)
	{
		return XPathHelper::isExpressionNumeric($expr);
	}

	/**
	* {@inheritdoc}
	*/
	protected function isSafe(Attribute $attribute)
	{
		return $attribute->isSafeInJS();
	}
}