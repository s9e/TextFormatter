#!/usr/bin/php
<?php declare(strict_types=1);

/*
See s9e\TextFormatter\Renderers\PHP::$quickRenderingTest
See s9e\TextFormatter\Renderers\PHP::render()

PHP 7.2.34 - PCRE 8.44 2020-02-12

         46 µs  (<[!?])
          3 µs  ((?<=<)[!?])

         49 µs  (<(?:[!?]|(?:FLASH|IMG)[ />]))
         34 µs  ((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))

         71 µs  (<[eis]>[^<]*+</[eis]>)
         80 µs  ((?<=\K<)[eis]>[^<]*+</[eis]>)
         70 µs  (<[eis]>[^>]++>)

PHP 8.0.0 - PCRE 10.36 2020-12-04

        217 µs  (<[!?])
         31 µs  ((?<=<)[!?])

        580 µs  (<(?:[!?]|(?:FLASH|IMG)[ />]))
         35 µs  ((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))

        415 µs  (<[eis]>[^<]*+</[eis]>)
        542 µs  ((?<=\K<)[eis]>[^<]*+</[eis]>)
        349 µs  (<[eis]>[^>]++>)

PHP 8.0.1 - PCRE 10.36 2020-12-04 JIT

          2 µs  (<[!?])
          1 µs  ((?<=<)[!?])

         42 µs  (<(?:[!?]|(?:FLASH|IMG)[ />]))
         27 µs  ((?<=<)(?:[!?]|(?:FLASH|IMG)[ />]))

         92 µs  (<[eis]>[^<]*+</[eis]>)
         91 µs  ((?<=\K<)[eis]>[^<]*+</[eis]>)
         90 µs  (<[eis]>[^>]++>)
*/

echo 'PHP ', PHP_VERSION, ' - PCRE ', PCRE_VERSION, (ini_get('pcre.jit') ? ' JIT' : ''), "\n\n";
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
	'(<[eis]>[^>]++>)',
];
benchmark($regexps, $input, '');

function benchmark(array $regexps, string $input, ?string $replace = null)
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