<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Traits;

trait TemplateSafeness
{
	/**
	* @var array Contexts in which this object is considered safe to be used
	*/
	protected $markedSafe = [];

	/**
	* Return whether this object is safe to be used as a URL
	*
	* @return bool
	*/
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	/**
	* Return whether this object is safe to be used in CSS
	*
	* @return bool
	*/
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}

	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		return $this->isSafe('InJS');
	}

	/**
	* Return whether this object is safe to be used as a URL
	*
	* @return bool
	*/
	public function markAsSafeAsURL()
	{
		return $this->markedSafe['AsURL'] = true;
	}

	/**
	* Return whether this object is safe to be used in CSS
	*
	* @return bool
	*/
	public function markAsSafeInCSS()
	{
		return $this->markedSafe['InCSS'] = true;
	}

	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return bool
	*/
	public function markAsSafeInJS()
	{
		return $this->markedSafe['InJS'] = true;
	}
}