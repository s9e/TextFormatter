<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Validators;
use InvalidArgumentException;
abstract class TagName
{
	public static function isValid($name)
	{
		return (bool) \preg_match('#^(?:(?!xmlns|xsl|s9e)[a-z_][a-z_0-9]*:)?[a-z_][-a-z_0-9]*$#Di', $name);
	}
	public static function normalize($name)
	{
		if (!static::isValid($name))
			throw new InvalidArgumentException("Invalid tag name '" . $name . "'");
		if (\strpos($name, ':') === \false)
			$name = \strtoupper($name);
		return $name;
	}
}