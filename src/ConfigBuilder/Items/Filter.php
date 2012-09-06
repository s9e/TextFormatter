<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Items;

use InvalidArgumentException;

class Filter
{
	/**
	* @var string|CallbackTemplate Either the name of a built-in filter, or a CallbackTemplate
	*                              instance
	*/
	protected $callback;

	/**
	* @var array Variables associated with this filter
	*/
	protected $vars;

	/**
	* @param string|CallbackTemplate $callback Either the name of a built-in filter, or a
	*                                          CallbackTemplate instance
	* @param array                   $vars     Variables associated with that filter
	*/
	public function __construct($callback, array $vars = array())
	{
		if (!($callback instanceof CallbackTemplate))
		{
			if (!is_string($callback) || $callback[0] !== '#')
			{
				throw new InvalidArgumentException('Argument 1 passed to Filter::__construct() must be a CallbackTemplate instance or the name of a built-in filter');
			}
		}

		$this->callback = $callback;
		$this->vars     = $vars;
	}

	/**
	* @return string|CallbackTemplate
	*/
	public function getCallback()
	{
		return $this->callback;
	}

	/**
	* @return array
	*/
	public function getVars()
	{
		return $this->vars;
	}

	/**
	* @param  array $vars
	* @return void
	*/
	public function setVars(array $vars)
	{
		$this->vars = $vars;
	}
}