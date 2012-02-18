<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use Iterator;

class Templateset implements ConfigProvider, Iterator
{
	/**
	* @var Tag
	*/
	protected $tag;

	/**
	* @var array
	*/
	protected $templates = array();

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
	* Remove all templates
	*/
	public function clear()
	{
		$this->templates = array();
	}

	public function getConfig()
	{
		return $this->templates;
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

		$this->templates[$predicate] = $template;
	}

	//==========================================================================
	// Iterator stuff
	//==========================================================================

	public function rewind()
	{
		reset($this->templates);
	}

	public function current()
	{
		return current($this->templates);
	}

	function key()
	{
		return key($this->templates);
	}

	function next()
	{
		return next($this->templates);
	}

	function valid()
	{
		return (key($this->templates) !== null);
	}
}