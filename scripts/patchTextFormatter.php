#!/usr/bin/php
<?php

class PHPUnit_Framework_TestCase {}

include __DIR__ . '/../tests/JSParserGeneratorTest.php';

$test = new s9e\TextFormatter\Tests\JSParserGeneratorTest;

$hints = array();
foreach ($test->getHintsData() as $args)
{
	if (!preg_match('#^HINT\\.([A-Za-z.0-9]+) is (false|true) (by default)?#', $args[0], $m))
	{
		continue;
	}

	$ref =& $hints;
	foreach (explode('.', $m[1]) as $k)
	{
		if (!isset($ref[$k]))
		{
			$ref[$k] = array();
		}

		$ref =& $ref[$k];
	}

	/**
	* There are two kinds of hints. Those based on the parser's config and those based on the
	* generator's (JSParserGenerator) config. Hints coming from the parser are meant to disable
	* routines that have no effects. Hints based on the generator's config are meant to disable
	* routines that the user does not intend to use.
	*
	* So hints from the parser config act as a whitelist: everything is disabled unless the config
	* shows that it is needed, whereas hints from the generator act as a blacklist; Everything must
	* remain functional unless the user requests it to be disabled.
	*
	* The stock configuration of hints is meant to keep everything functional. Therefore, the stock
	* configuration copies the default state of generator-based hints and uses the opposite state of
	* parser-based hints. We can detect generator-based hints by looking for the array of options
	* passed to the generator to trigger the *opposite* state
	*/
	if (!empty($m[3]) || !empty($args[2]))
	{
		$ref = ($m[2] === 'false') ? 'true' : 'false';
	}
}

function format(array $arr, $indent)
{
	$str = $sep = '';

	ksort($arr);

	foreach ($arr as $k => $v)
	{
		$str .= $sep . $indent . $k . ': ';
		$sep = ',';

		if (is_array($v))
		{
			$str .= "{" . format($v, $indent . "\t") . $indent . "}";
		}
		else
		{
			$str .= $v;
		}
	}

	return $str;
}

$filepath = __DIR__ . '/../src/TextFormatter.js';

file_put_contents(
	$filepath,
	preg_replace(
		'#(// START OF STOCK HINTS - DO NOT EDIT).*?(// END OF STOCK HINTS - DO NOT EDIT)#s',
		"\$1\nvar HINT = {" . format($hints, "\n\t") . "\n};\n\$2",
		file_get_contents($filepath)
	)
);

die("Done.\n");
