<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use InvalidArgumentException;

class Attribute extends ConfigurableItem
{
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

		$this->setOptions($options);
	}

	/**
	* Return whether a string is a valid attribute name
	*
	* @param  string $name
	* @return bool
	*/
	static public function isValidName($name)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9\\-]*$#Di', $name);
	}

	/**
	* Validate and normalize an attribute name
	*
	* @param  string $name Original attribute name
	* @return string       Normalized attribute name, in lowercase
	*/
	static public function normalizeName($name)
	{
		if (!self::isValidName($name))
		{
			throw new InvalidArgumentException ("Invalid attribute name '" . $name . "'");
		}

		return strtolower($name);
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