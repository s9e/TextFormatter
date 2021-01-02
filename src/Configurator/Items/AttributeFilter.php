<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Items;

use s9e\TextFormatter\Configurator\Traits\TemplateSafeness;

class AttributeFilter extends Filter
{
	use TemplateSafeness;

	/**
	* Constructor
	*
	* @param  callable $callback
	*/
	public function __construct($callback)
	{
		parent::__construct($callback);

		// Set the default signature
		$this->resetParameters();
		$this->addParameterByName('attrValue');
	}

	/**
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
		$safeCallbacks = [
			'urlencode',
			'strtotime',
			'rawurlencode'
		];

		if (in_array($this->callback, $safeCallbacks, true))
		{
			return true;
		}

		return $this->isSafe('InJS');
	}
}