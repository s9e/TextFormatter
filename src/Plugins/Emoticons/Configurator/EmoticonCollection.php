<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons\Configurator;

use s9e\TextFormatter\Configurator\Collections\NormalizedCollection;
use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;

class EmoticonCollection extends NormalizedCollection
{
	/**
	* Normalize an emoticon's template
	*
	* NOTE: this allows the HTML syntax to be used for individual emoticons
	*
	* @param  string $value Emoticon's original markup
	* @return string        Normalized template
	*/
	public function normalizeValue($value)
	{
		return TemplateHelper::saveTemplate(TemplateHelper::loadTemplate($value));
	}
}