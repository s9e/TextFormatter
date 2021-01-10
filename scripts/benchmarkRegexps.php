#!/usr/bin/php
<?php declare(strict_types=1);

/*
See s9e\TextFormatter\Renderers\PHP::$quickRenderingTest
See s9e\TextFormatter\Renderers\PHP::render()

PHP 7.2.34 - PCRE 8.44 2020-02-12

         45 µs  (<[!?])
          2 µs  ((?<=<)[!?])

         48 µs  (<(?:[!?]|(?:FLASH|IMG)[ />]))
         23 µs  ((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))

         69 µs  (<[eis]>[^<]*+</[eis]>)
         79 µs  ((?<=\K<)[eis]>[^<]*+</[eis]>)

PHP 8.0.0 - PCRE 10.36 2020-12-04

        216 µs  (<[!?])
         26 µs  ((?<=<)[!?])

        502 µs  (<(?:[!?]|(?:FLASH|IMG)[ />]))
         24 µs  ((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))

        431 µs  (<[eis]>[^<]*+</[eis]>)
        377 µs  ((?<=\K<)[eis]>[^<]*+</[eis]>)
*/

echo 'PHP ', PHP_VERSION, ' - PCRE ', PCRE_VERSION, "\n\n";
$input = str_repeat('<B><s>[b]</s>Lorem ipsum<e>[/b]</e></B> ', 1000);

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

$regexps = [
	'(<[eis]>[^<]*+</[eis]>)',
	'((?<=\\K<)[eis]>[^<]*+</[eis]>)',
];
benchmark($regexps, $input, '');

function benchmark(array $regexps, string $input, string $replace = null)
{
	foreach ($regexps as $regexp)
	{
		$i = 1e3;

		$s=microtime(true);
		do
		{
			(isset($replace)) ? preg_replace($regexp, $replace, $input) : preg_match($regexp, $input);
		}
		while (--$i);
		$e=microtime(true);

		echo sprintf('%11s µs', round(1e3 * ($e - $s))), "\t", $regexp, "\n";
	}
	echo "\n";
}