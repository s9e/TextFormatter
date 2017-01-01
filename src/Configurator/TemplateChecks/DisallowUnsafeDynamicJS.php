<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateChecks;

use DOMElement;
use s9e\TextFormatter\Configurator\Helpers\XPathHelper;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Attribute;

class DisallowUnsafeDynamicJS extends AbstractDynamicContentCheck
{
	/**
	* {@inheritdoc}
	*/
	protected function getNodes(DOMElement $template)
	{
		return TemplateHelper::getJSNodes($template->ownerDocument);
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