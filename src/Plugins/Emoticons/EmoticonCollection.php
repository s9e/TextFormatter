<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoticons;

use s9e\TextFormatter\Generator\Collections\NormalizedCollection;
use s9e\TextFormatter\Generator\Helpers\TemplateChecker;
use s9e\TextFormatter\Generator\Helpers\TemplateOptimizer;
use s9e\TextFormatter\Generator\Items\Tag;

class EmoticonCollection extends NormalizedCollection
{
	/**
	* Normalize an emoticon's template
	*
	* @param  string $value Emoticon's original markup
	* @return string        Normalized template
	*/
	public function normalizeValue($value)
	{
		// We test this template's safety here even though it will be tested again when it is
		// actually assigned to the tag in order to fail early, should the template be unsafe
		$template = TemplateOptimizer::optimize($value);
		TemplateChecker::checkUnsafe($template, new Tag);

		return $template;
	}
}