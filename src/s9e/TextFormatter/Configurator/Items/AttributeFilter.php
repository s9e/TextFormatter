<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

class AttributeFilter extends Filter
{
	/**
	* Constructor
	*
	* @param  callable $callback
	* @return void
	*/
	public function __construct($callback)
	{
		parent::__construct($callback);

		// Set the default signature
		$this->resetParameters();
		$this->addParameterByName('attrValue');
	}

	/**
	* Return whether this filter makes a value safe to be used in CSS
	*
	* @return bool
	*/
	public function isSafeInCSS()
	{
		return false;
	}

	/**
	* Return whether this filter makes a value safe to be used in Javascript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		return false;
	}

	/**
	* Return whether this filter makes a value safe to be used in a URL
	*
	* @return bool
	*/
	public function isSafeInURL()
	{
		return false;
	}
}