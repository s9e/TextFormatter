<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

class AttributeFilter extends Filter
{
	/*
	* @var array Contexts in which this object is considered safe to be used
	*/
	protected $markedSafe = array();

	/*
	* Return whether this object is safe to be used in given context
	*
	* @param  string $context Either 'AsURL', 'InCSS' or 'InJS'
	* @return bool
	*/
	protected function isSafe($context)
	{
		// Test whether this attribute was marked as safe in given context
		return !empty($this->markedSafe[$context]);
	}

	/*
	* Return whether this object is safe to be used as a URL
	*
	* @return bool
	*/
	public function isSafeAsURL()
	{
		return $this->isSafe('AsURL');
	}

	/*
	* Return whether this object is safe to be used in CSS
	*
	* @return bool
	*/
	public function isSafeInCSS()
	{
		return $this->isSafe('InCSS');
	}



	/*
	* Return whether this object is safe to be used as a URL
	*
	* @return self
	*/
	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = \true;

		return $this;
	}

	/*
	* Return whether this object is safe to be used in CSS
	*
	* @return self
	*/
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = \true;

		return $this;
	}

	/*
	* Return whether this object is safe to be used in JavaScript
	*
	* @return self
	*/
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = \true;

		return $this;
	}

	/*
	* Reset the "marked safe" statuses
	*
	* @return self
	*/
	public function resetSafeness()
	{
		$this->markedSafe = array();

		return $this;
	}

	/*
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

	/*
	* Return whether this filter makes a value safe to be used in JavaScript
	*
	* @return bool
	*/
	public function isSafeInJS()
	{
		// List of callbacks that make a value safe to be used in a script, hardcoded for
		// convenience. Technically, there are numerous built-in PHP functions that would make an
		// arbitrary value safe in JS, but only a handful have the potential to be used as an
		// attribute filter
		$safeCallbacks = array(
			'urlencode',
			'strtotime',
			'rawurlencode'
		);

		if (\in_array($this->callback, $safeCallbacks, \true))
			return \true;

		return $this->isSafe('InJS');
	}
}