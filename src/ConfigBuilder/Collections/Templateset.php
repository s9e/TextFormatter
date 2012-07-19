<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

class Templateset extends Collection
{
	/**
	* @var Tag
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
	* 
	*
	* @return void
	*/
	public function set($predicate, $template)
	{
		$template = TemplateOptimizer::optimize($template);

		TemplateHelper::checkUnsafe($template, $this->tag);

		$this->items[$predicate] = $template;
	}

	/**
	* 
	*
	* @return void
	*/
	public function setUnsafe($predicate, $template)
	{
		$template = TemplateOptimizer::optimize($template);

		$this->items[$predicate] = $template;
	}
}