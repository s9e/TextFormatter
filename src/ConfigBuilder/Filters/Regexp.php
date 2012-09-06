<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\ConfigBuilder\Filters;

use s9e\TextFormatter\ConfigBuilder\Exceptions\InvalidFilterException;

abstract class Regexp
{
	public static function formatConfig(array $vars)
	{
		if (!isset($vars['regexp']))
		{
			throw new InvalidFilterException("Variable 'regexp' is missing");
		}

		if (@preg_match($vars['regexp'], '') === false)
		{
			throw new InvalidFilterException("Invalid regular expression");
		}

		return array('regexp' => $vars['regexp']);
	}
}