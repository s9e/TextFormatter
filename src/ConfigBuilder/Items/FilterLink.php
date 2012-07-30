<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Items;

use InvalidArgumentException;

class FilterLink
{
	/**
	* @var string|Filter Either the name of a built-in filter, or a Filter instance
	*/
	protected $filter;

	/**
	* @var array         Config associated with that filter
	*/
	protected $filterConfig;

	/**
	* @param string|Filter $filter       Either the name of a built-in filter, or a Filter instance
	* @param array         $filterConfig Config associated with that filter
	*/
	public function __construct($filter, array $filterConfig)
	{
		if (!($filter instanceof Filter))
		{
			if (!is_string($filter) || $filter[0] !== '#')
			{
				throw new InvalidArgumentException('Argument 1 passed to FilterLink::__construct() must be a Filter instance or the name of a built-in filter');
			}
		}

		$this->filter       = $filter;
		$this->filterConfig = $filterConfig;
	}

	/**
	* @return string|Filter
	*/
	public function getFilter()
	{
		return $this->filter;
	}

	/**
	* @return array
	*/
	public function getFilterConfig()
	{
		return $this->filterConfig;
	}
}