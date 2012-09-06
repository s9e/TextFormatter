<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Items;

use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\Collections\FilterChain;
use s9e\TextFormatter\ConfigBuilder\Traits\Configurable;

class Attribute
{
	use Configurable;

	/**
	* @var FilterChain This attribute's filter chain
	*/
	protected $filterChain;

	/**
	* @var bool Whether this attribute is required for the tag to be valid
	*/
	protected $required = true;

	/**
	* @param array $options This attribute's options
	*/
	public function __construct(array $options = null)
	{
		$this->filterChain = new FilterChain(array('attrVal' => null));

		if (isset($options))
		{
			foreach ($options as $optionName => $optionValue)
			{
				$this->__set($optionName, $optionValue);
			}
		}
	}

	//==========================================================================
	// Setters
	//==========================================================================

	/**
	* @param FilterChain|array $filterChain
	*/
	public function setFilterChain($filterChain)
	{
		if ($filterChain instanceof FilterChain)
		{
			$this->filterChain = $filterChain;
		}
		elseif (is_array($filterChain))
		{
			$this->filterChain->clear();

			foreach ($filterChain as $filter)
			{
				$this->filterChain->append($filter);
			}
		}
		else
		{
			throw new InvalidArgumentException('setFilterChain() expects an array or an instance of FilterChain');
		}
	}
}