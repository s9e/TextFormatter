#!/usr/bin/php
<?php

/*
See s9e\TextFormatter\Renderers\PHP::$quickRenderingTest

PHP 7.2.29 - PCRE 8.44 2020-02-12

         15 µs  (<[!?])
          1 µs  ((?<=<)[!?])

         17 µs  (<(?:[!?]|(?:FLASH|IMG)[ />]))
          7 µs  ((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))

PHP 7.4.4 - PCRE 10.34 2019-11-21

         78 µs  (<[!?])
          7 µs  ((?<=<)[!?])

        188 µs  (<(?:[!?]|(?:FLASH|IMG)[ />]))
          7 µs  ((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))
*/

echo 'PHP ', PHP_VERSION, ' - PCRE ', PCRE_VERSION, "\n\n";
$input = str_repeat('<B>..</B>', 1e3);

$regexps = [
	'(<[!?])',
	'((?<=<)[!?])'
];
benchmark($regexps, $input);

$regexps = [
	'(<(?:[!?]|(?:FLASH|IMG)[ />]))',
	'((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))'
];
benchmark($regexps, $input);

function benchmark(array $regexps, string $input)
{
	foreach ($regexps as $regexp)
	{
		$i = 1e3;

		$s=microtime(true);
		do
		{
			preg_match($regexp, $input);
		}
		while (--$i);
		$e=microtime(true);

		echo sprintf('%11s µs', round(1e3 * ($e - $s))), "\t", $regexp, "\n";
	}
	echo "\n";
}