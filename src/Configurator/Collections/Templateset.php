<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Helpers\TemplateHelper;
use s9e\TextFormatter\Configurator\Items\Tag;

/**
* @todo check the predicate for well-formedness in normalizeKey() - also ensure that entities in predicate are escaped either here or when the final stylesheet is assembled
*/
class Templateset extends NormalizedCollection
{
	/**
	* @var Tag Tag used by TemplateChecker to assess the safeness of attributes used in templates
	*/
	protected $tag;

	/**
	* Constructor
	*
	* @param Tag $tag Tag that these templates belong to
	*/
	public function __construct(Tag $tag)
	{
		$this->tag = $tag;
	}

	/**
	* Normalize a template for storage
	*
	* @param  string $template Original template
	* @return string           Normalized template
	*/
	public function normalizeValue($template)
	{
		return TemplateHelper::normalize($template, $this->tag);
	}

	/**
	* Set a template without checking it for unsafe markup
	*
	* @param  string $predicate Template's predicate
	* @param  string $template  Template's content
	* @return void
	*/
	public function setUnsafe($predicate, $template)
	{
		$predicate = $this->normalizeKey($predicate);
		$template  = TemplateHelper::normalizeUnsafe($template, $this->tag);

		$this->items[$predicate] = $template;
	}
}