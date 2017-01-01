<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2017 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;

use DOMElement;
use DOMNodeList;
use s9e\TextFormatter\Configurator\TemplateNormalization;

class SetRelNoreferrerOnTargetedLinks extends TemplateNormalization
{
	/**
	* Add rel="noreferrer" on link elements with a target attribute
	*
	* Adds a "noreferrer" on links that open in a new context that would allow window.opener to be
	* accessed.
	*
	* @link https://mathiasbynens.github.io/rel-noopener/
	* @link https://wiki.whatwg.org/wiki/Links_to_Unrelated_Browsing_Contexts
	*
	* @param  DOMElement $template <xsl:template/> node
	* @return void
	*/
	public function normalize(DOMElement $template)
	{
		$this->normalizeElements($template->ownerDocument->getElementsByTagName('a'));
		$this->normalizeElements($template->ownerDocument->getElementsByTagName('area'));
	}

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
	* Normalize a list of links
	*
	* @param  DOMNodeList $elements
	* @return void
	*/
	protected function normalizeElements(DOMNodeList $elements)
	{
		foreach ($elements as $element)
		{
			if ($this->linkTargetCanAccessOpener($element))
			{
				$this->addRelAttribute($element);
			}
		}
	}
}