<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Parser;

trait FilterProcessing
{
	/**
	* 
	*
	* @return bool
	*/
	protected function executeFilterChain(array $filterChain)
	{
		foreach ($filterChain as $filter)
		{
			// TODO: built-in filters, e.g. #int or #filterAttributes -- perhaps use a FilterLocator
			//       Also, how to reinject the return value into the filter vars while filtering
			//       attributes
			$value = $this->executeFilter($filter);

			if ($value === false)
			{
				break;
			}
		}
	}

	/**
	* 
	*
	* @return mixed
	*/
	protected function executeFilter(array $filter)
	{
		$callback = $filter['callback'];
		$params   = (isset($filter['params'])) ? $filter['params'] : array();
	}
}