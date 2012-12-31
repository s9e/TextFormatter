<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use InvalidArgumentException;

class Template
{
	/**
	* @var callback
	*/
	protected $callback;

	/**
	* @var callback
	*/
	protected $template;

	/**
	* Constructor
	*
	* @param  callback|string $arg
	* @return void
	*/
	public function __construct($arg)
	{
		if (is_callable($arg))
		{
			$this->callback = $arg;
		}
		elseif (is_string($arg))
		{
			$this->template = $arg;
		}
		else
		{
			throw new InvalidArgumentException('Argument 1 passed to ' . __METHOD__ . ' must be a string or a valid callback');
		}
	}

	/**
	* Return this template in string form, executing the stored callback if applicable
	*
	* @return string
	*/
	public function __toString()
	{
		return (isset($this->callback))
		     ? call_user_func($this->callback)
		     : $this->template;
	}
}