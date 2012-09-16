<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Helpers\TemplateChecker;
use s9e\TextFormatter\ConfigBuilder\Helpers\TemplateOptimizer;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

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
	* Will optimize the template and check for unsafe content
	*
	* @see s9e\ConfigBuilder\Helpers\TemplateChecker
	* @see s9e\ConfigBuilder\Helpers\TemplateOptimizer
	*
	* @param  string $template Original template
	* @return string           Normalized template
	*/
	public function normalizeValue($template)
	{
		// We optimize the template before checking for unsafe elements because the optimizer tends
		// to simplify the templates, which should make checking for unsafe elements easier
		$template = $this->optimize($template);

		$this->checkUnsafe($template);

		return $template;
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
		$template  = $this->optimize($template);

		$this->items[$predicate] = $template;
	}

	/**
	* Check a given template for safeness
	*
	* @throws s9e\TextFormatter\ConfigBuilder\Exceptions\UnsafeTemplateException
	*
	* @param  string $template
	* @return void
	*/
	public function checkUnsafe($template)
	{
		TemplateChecker::checkUnsafe($template, $this->tag);
	}

	/**
	* Optimize a template
	*
	* @param  string $template Original template
	* @return string           Optimized template
	*/
	public function optimize($template)
	{
		return TemplateOptimizer::optimize($template);
	}
}