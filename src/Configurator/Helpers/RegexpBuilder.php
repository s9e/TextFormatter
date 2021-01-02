<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use s9e\RegexpBuilder\Builder;

abstract class RegexpBuilder
{
	/**
	* Create a regexp pattern that matches a list of words
	*
	* @param  array  $words   Words to sort (must be UTF-8)
	* @param  array  $options
	* @return string
	*/
	public static function fromList(array $words, array $options = [])
	{
		$options += [
			'delimiter'       => '/',
			'caseInsensitive' => false,
			'specialChars'    => [],
			'unicode'         => true
		];

		// Normalize ASCII if the regexp is meant to be case-insensitive
		if ($options['caseInsensitive'])
		{
			foreach ($words as &$word)
			{
				$word = strtr($word, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
			}
			unset($word);
		}

		$builder = new Builder([
			'delimiter' => $options['delimiter'],
			'meta'      => $options['specialChars'],
			'input'     => $options['unicode'] ? 'Utf8' : 'Bytes',
			'output'    => $options['unicode'] ? 'Utf8' : 'Bytes'
		]);

		return $builder->build($words);
	}
}