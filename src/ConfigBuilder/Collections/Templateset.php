<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Collections;

use s9e\TextFormatter\ConfigBuilder\Helpers\TemplateHelper;
use s9e\TextFormatter\ConfigBuilder\Items\Tag;

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
		$this->setTemplate($predicate, $template, true);
	}

	/**
	* 
	*
	* @return void
	*/
	public function setUnsafe($predicate, $template)
	{
		$this->setTemplate($predicate, $template, false);
	}

	/**
	* 
	*
	* @param string $template
	*/
	protected function setTemplate($predicate, $template, $checkUnsafe)
	{
		// We optimize the template before checking for unsafe elements because the optimizer tends
		// to simplify the templates, which should make checking for unsafe elements easier
		$template = $this->optimize($template);

		if ($checkUnsafe)
		{
			$this->checkUnsafe($template);
		}

		$this->items[$predicate] = $template;
	}

	/**
	* 
	*
	* @param string $template
	*/
	public function checkUnsafe($template)
	{
		TemplateHelper::checkUnsafe($template, $this->tag);
	}

	/**
	* 
	*
	* @param  string $template
	* @return string
	*/
	public function optimize($template)
	{
		return TemplateOptimizer::optimize($template);
	}
}