<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder;

use s9e\TextFormatter\RegexpMaster;

class AttributeParser implements Item
{
	/**
	* @todo parse the regexp, reject multiple subpatterns that use the same name
	*
	* @param string $regexp
	*/
	public function __construct($regexp)
	{
		$this->regexp = $regexp;
	}

	/**
	* Return whether a string is a valid attribute parser name
	*
	* @param  string $name
	* @return bool
	*/
	static public function isValidName($name)
	{
		return (bool) preg_match('#^[a-z_][a-z_0-9\\-]*$#Di', $name);
	}

	/**
	* Validate and normalize an attribute parser name
	*
	* @param  string $name Original attribute parser name
	* @return string       Normalized attribute parser name, in lowercase
	*/
	static public function normalizeName($name)
	{
		if (!self::isValidName($name))
		{
			throw new InvalidArgumentException ("Invalid attribute parser name '" . $name . "'");
		}

		return strtolower($name);
	}
}