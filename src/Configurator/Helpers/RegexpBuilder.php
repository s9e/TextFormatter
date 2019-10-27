<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2019 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;
use s9e\RegexpBuilder\Builder;
abstract class RegexpBuilder
{
	public static function fromList(array $words, array $options = array())
	{
		$options += array(
			'delimiter'       => '/',
			'caseInsensitive' => \false,
			'specialChars'    => array(),
			'unicode'         => \true
		);
		if ($options['caseInsensitive'])
		{
			foreach ($words as &$word)
				$word = \strtr($word, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz');
			unset($word);
		}
		$builder = new Builder(array(
			'delimiter' => $options['delimiter'],
			'meta'      => $options['specialChars'],
			'input'     => $options['unicode'] ? 'Utf8' : 'Bytes',
			'output'    => $options['unicode'] ? 'Utf8' : 'Bytes'
		));
		return $builder->build($words);
	}
}