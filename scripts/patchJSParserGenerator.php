#!/usr/bin/php
<?php

function generateRange($start, $end)
{
	$str = pcreChr($start);

	if ($end > $start)
	{
		if ($end > $start + 1)
		{
			$str .= '-';
		}
		$str .= pcreChr($end);
	}

	return $str;
}

function pcreChr($cp)
{
	if ($cp >= 32 && $cp <= 126)
	{
		return preg_quote(chr($cp));
	}

	return '\\u' . sprintf('%04X', $cp);
}

if (!file_exists('/tmp/props.txt'))
{
	file_put_contents(
		'/tmp/props.txt',
		file_get_contents('http://unicode.org/Public/UNIDATA/PropList.txt') . "\n" . file_get_contents('http://unicode.org/Public/UNIDATA/DerivedCoreProperties.txt')
	);
}

$supportedProperties = array_flip(array(
	'L&', 'Ll', 'Lm', 'Lo', 'Lt', 'Lu',
	'Nd', 'Nl', 'No',
	'Pc', 'Pd', 'Pe', 'Pf', 'Pi', 'Po', 'Ps',
	'Sc', 'Sk', 'Sm', 'So',
	'Zl', 'Zp', 'Zs'
));

// only support \pL for now
$supportedProperties = array('L&' => 1);

$lines = file('/tmp/props.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$ranges = array();
foreach ($lines as $line)
{
	if ($line[0] === '#'
	 || ($line[4] !== ' ' && $line[4] !== '.'))
	{
		// Ignore comments and stuff outside of the BMP
		continue;
	}

	$propName = substr($line, strpos($line, '# ') + 2, 2);

	if (!isset($supportedProperties[$propName]))
	{
		continue;
	}

	$range = substr($line, 0, 4) . substr($line, 6 * ($line[4] === '.'), 4);

	$ranges[$propName][$range] = 0;
	$ranges[$propName[0]][$range] = 0;
}

unset($ranges['L&']);

foreach ($ranges as $propName => $propRanges)
{
	ksort($propRanges, SORT_STRING);

	$str = '';

	unset($bufStart, $bufEnd);

	foreach ($propRanges as $range => $void)
	{
		$curStart = hexdec(substr($range, 0, 4));
		$curEnd   = hexdec(substr($range, 4));

		if (!isset($bufStart))
		{
			$bufStart = $curStart;
			$bufEnd   = $curEnd;
			continue;
		}

		if ($curStart <= $bufEnd + 1)
		{
			// extend the buffered range
			$bufEnd = max($bufEnd, $curEnd);
		}
		else
		{
			// dump the buffered range and unset it
			$str .= generateRange($bufStart, $bufEnd);

			$bufStart = $curStart;
			$bufEnd   = $curEnd;
		}
	}

	if (isset($bufStart))
	{
		$str .= generateRange($bufStart, $bufEnd);
	}

	$props[$propName] = $str;
}
ksort($props);

$php = trim(var_export($props, true), "ary (\r\n),");
$php = str_replace("\r\n", "\n", $php);
$php = str_replace('  ', "\t\t", $php);

$php = " = array(\n\t\t" . $php . "\n\t)";

$file = file_get_contents(__DIR__ . '/../src/TextFormatter/JSParserGenerator.php');
$file = preg_replace(
	'#(static public \\$unicodeProps)(.*?)\\n\\t\\);#s',
	'$1;',
	$file
);

$file = str_replace(
	'static public $unicodeProps;',
	'static public $unicodeProps' . $php . ';',
	$file
);

file_put_contents(__DIR__ . '/../src/TextFormatter/JSParserGenerator.php', $file);

echo "Done.\n";