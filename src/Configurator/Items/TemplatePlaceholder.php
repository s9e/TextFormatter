<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;

class TemplatePlaceholder
{
	/**
	* @var bool Whether to allow unsafe markup
	*/
	protected $allowUnsafe = false;

	/**
	* @var callback
	*/
	protected $callback;

	/**
	* Constructor
	*
	* @param  callback $callback
	* @return void
	*/
	public function __construct($callback)
	{
		if (!is_callable($callback))
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be callable');
		}

		$this->callback = $callback;
	}

	/**
	* Execute the stored callback and return the result
	*
	* @return mixed
	*/
	public function __toString()
	{
		return call_user_func($this->callback);
	}

	/**
	* Return whether this template allows unsafe markup
	*
	* @return bool
	*/
	public function allowsUnsafeMarkup()
	{
		return $this->allowUnsafe;
	}

	/**
	* Disable template checking to allow unsafe markup in this template
	*
	* @return void
	*/
	public function disableTemplateChecking()
	{
		$this->allowUnsafe = true;
	}

	/**
	* Enable template checking to detect unsafe markup in this template
	*
	* @return void
	*/
	public function enableTemplateChecking()
	{
		$this->allowUnsafe = false;
	}
}