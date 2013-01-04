<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;

use InvalidArgumentException;

/**
* Stylesheet parameter name rules:
*  - must start with a letter or an underscore
*  - can only contain letters, numbers and underscores
*
* This is much more restrictive than the XSL specs
*/
abstract class StylesheetParameterName
{
	/**
	* Return whether a string is a valid parameter name
	*
	* @param  string $name Parameter name
	* @return bool
	*/
	public static function isValid($name)
	{
		return (bool) preg_match('#^[a-z_][-a-z_0-9]*$#Di', $name);
	}

	/**
	* {@inheritdoc}
	*/
	public static function normalize($name)
	{
		if (!static::isValid($name))
		{
			throw new InvalidArgumentException("Invalid parameter name '" . $name . "'");
		}

		return $name;
	}
}