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
	public function set($template, $predicate = '')
	{
		$template = TemplateOptimizer::optimize($template);

		if (!$this->allowUnsafeTemplates)
		{
			$unsafeMsg = TemplateHelper::checkUnsafe($template, $this->tag);

			if ($unsafeMsg)
			{
				throw new RuntimeException($unsafeMsg);
			}
		}

		$this->items[$predicate] = $template;
	}
}