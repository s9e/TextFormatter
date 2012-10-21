<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Generator\Filters;

use s9e\TextFormatter\Generator\Exceptions\InvalidFilterException;

abstract class Range
{
	public static function formatConfig(array $vars)
	{
		$config = array();

		foreach (array('min', 'max') as $varName)
		{
			if (!isset($vars[$varName]))
			{
				throw new InvalidFilterException("Variable '" . $varName . "' is missing");
			}

			$value = filter_var($vars[$varName], FILTER_VALIDATE_FLOAT);

			if ($value === false)
			{
				throw new InvalidFilterException("Variable '" . $varName . "' must be a number");
			}

			$config[$varName] = $value;
		}

		if ($vars['min'] > $vars['max'])
		{
			throw new InvalidFilterException("The 'max' value must be greater or equal to the 'min' value");
		}

		if (isset($vars['step']))
		{
			$value = filter_var($vars['step'], FILTER_VALIDATE_FLOAT);

			if ($value === false)
			{
				throw new InvalidFilterException("Variable 'step' must be a number greater than 0");
			}

			$config['step'] = $value;
		}

		return $config;
	}
}