<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Items;

use InvalidArgumentException;
use s9e\TextFormatter\ConfigBuilder\Collections\FilterChain;

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
	public function __construct(array $options = array())
	{
		$this->filterChain = new FilterChain(array('attrVal' => null));

		foreach ($options as $optionName => $optionValue)
		{
			$this->__set($optionName, $optionValue);
		}
	}

	//==========================================================================
	// Setters
	//==========================================================================

	/**
	* @param string $regexp
	*/
	public function setRegexp($regexp)
	{
		if (@preg_match($regexp, '') === false)
		{
			throw new InvalidArgumentException('Invalid regexp');
		}

		$this->regexp = $regexp;
	}

	/**
	* @param array $filterChain
	*/
	public function setFilterChain(array $filterChain)
	{
		$this->filterChain->clear();

		foreach ($filterChain as $filter)
		{
			$this->filterChain->append($filter);
		}
	}
}