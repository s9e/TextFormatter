<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;

use InvalidArgumentException;

/**
* Attribute name rules:
*  - must start with a letter or an underscore
*  - can only contain letters, numbers, underscores and dashes
*
* Unprefixed names are normalized to uppercase. Prefixed names are preserved as-is.
*/
abstract class AttributeName
{
	/**
	* Return whether a string is a valid attribute name
	*
	* @param  string $name
	* @return bool
	*/
	public static function isValid($name)
	{
		return (bool) preg_match('#^(?!xmlns$)[a-z_][-a-z_0-9]*$#Di', $name);
	}

	/**
	* Normalize a tag name
	*
	* @throws InvalidArgumentException if the original name is not valid
	*
	* @param  string $name Original name
	* @return string       Normalized name
	*/
	public static function normalize($name)
	{
		if (!static::isValid($name))
		{
			throw new InvalidArgumentException("Invalid attribute name '" . $name . "'");
		}

		return strtolower($name);
	}
}