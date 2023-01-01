<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;

/**
* Add rel="noreferrer" on links that open in a new context that would allow window.opener to be
* accessed.
*
* @link https://mathiasbynens.github.io/rel-noopener/
* @link https://wiki.whatwg.org/wiki/Links_to_Unrelated_Browsing_Contexts
*/
class SetRelNoreferrerOnTargetedLinks extends AddAttributeValueToElements
{
	/**
	* {@inheritdoc}
	*/
	public function __construct(string $query = '//a[@target] | //area[@target]', string $attrName = 'rel', string $value = 'noreferrer')
	{
		parent::__construct($query, $attrName, $value);
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element): void
	{
		if (!preg_match('(\\bno(?:open|referr)er\\b)', $element->getAttribute('rel')))
		{
			parent::normalizeElement($element);
		}
	}
}