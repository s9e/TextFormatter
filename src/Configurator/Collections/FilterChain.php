<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Collections;

use s9e\TextFormatter\Configurator\Items\ProgrammableCallback;

class FilterChain extends NormalizedList
{
	/**
	* Test whether a given callback is present in this filter chain
	*
	* NOTE: does not differentiate signatures, only the PHP callback
	*
	* @param  callback $callback PHP callback
	* @return bool               Whether the callback was found in this filter chain
	*/
	public function containsCallback($callback)
	{
		// Normalize the $callback using the Filter object
		$callback = (new ProgrammableCallback($callback))->getCallback();

		foreach ($this->items as $filter)
		{
			if ($filter->getCallback() === $callback)
			{
				return true;
			}
		}

		return false;
	}
}