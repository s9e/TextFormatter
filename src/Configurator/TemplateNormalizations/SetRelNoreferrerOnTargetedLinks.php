<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
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
class SetRelNoreferrerOnTargetedLinks extends AbstractNormalization
{
	/**
	* {@inheritdoc}
	*/
	protected $queries = ['//a', '//area'];

	/**
	* Add a rel="noreferrer" attribute to given element
	*
	* @param  DOMElement $element
	* @return void
	*/
	protected function addRelAttribute(DOMElement $element)
	{
		$rel = $element->getAttribute('rel');
		if (preg_match('(\\S$)', $rel))
		{
			$rel .= ' ';
		}
		$rel .= 'noreferrer';

		$element->setAttribute('rel', $rel);
	}

	/**
	* Test whether given link element will let the target access window.opener
	*
	* @param  DOMElement $element
	* @return bool
	*/
	protected function linkTargetCanAccessOpener(DOMElement $element)
	{
		// Can't access window.opener if the link doesn't have a target
		if (!$element->hasAttribute('target'))
		{
			return false;
		}

		// Can't access window.opener if the link already has rel="noopener" or rel="noreferrer"
		if (preg_match('(\\bno(?:open|referr)er\\b)', $element->getAttribute('rel')))
		{
			return false;
		}

		return true;
	}

	/**
	* {@inheritdoc}
	*/
	protected function normalizeElement(DOMElement $element)
	{
		if ($this->linkTargetCanAccessOpener($element))
		{
			$this->addRelAttribute($element);
		}
	}
}