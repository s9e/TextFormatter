<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2020 The s9e authors
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
	* @return self
	*/
	public function markAsSafeAsURL()
	{
		$this->markedSafe['AsURL'] = true;

		return $this;
	}

	/**
	* Return whether this object is safe to be used in CSS
	*
	* @return self
	*/
	public function markAsSafeInCSS()
	{
		$this->markedSafe['InCSS'] = true;

		return $this;
	}

	/**
	* Return whether this object is safe to be used in JavaScript
	*
	* @return self
	*/
	public function markAsSafeInJS()
	{
		$this->markedSafe['InJS'] = true;

		return $this;
	}

	/**
	* Reset the "marked safe" statuses
	*
	* @return self
	*/
	public function resetSafeness()
	{
		$this->markedSafe = [];

		return $this;
	}
}