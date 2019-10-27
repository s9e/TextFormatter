<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\TemplateNormalizations;
use DOMElement;
class SetRelNoreferrerOnTargetedLinks extends AbstractNormalization
{
	protected $queries = array('//a', '//area');
	protected function addRelAttribute(DOMElement $element)
	{
		$rel = $element->getAttribute('rel');
		if (\preg_match('(\\S$)', $rel))
			$rel .= ' ';
		$rel .= 'noreferrer';
		$element->setAttribute('rel', $rel);
	}
	protected function linkTargetCanAccessOpener(DOMElement $element)
	{
		if (!$element->hasAttribute('target'))
			return \false;
		if (\preg_match('(\\bno(?:open|referr)er\\b)', $element->getAttribute('rel')))
			return \false;
		return \true;
	}
	protected function normalizeElement(DOMElement $element)
	{
		if ($this->linkTargetCanAccessOpener($element))
			$this->addRelAttribute($element);
	}
}