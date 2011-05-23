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

//$file = file_get_contents('http://unicode.org/Public/UNIDATA/PropList.txt');
$lines = file('/tmp/PropList.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$ranges = array();
foreach ($lines as $line)
{
	if ($line[0] === '#')
	{
		continue;
	}

	$propName = substr($line, strpos($line, '# ') + 2, 2);

	$range = substr($line, 0, 4) . substr($line, 6 * ($line[4] === '.'), 4);

	$ranges[$propName][$range] = 0;
	$ranges[$propName[0]][$range] = 0;
}

foreach ($ranges as $propName => $propRanges)
{
	ksort($propRanges);

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
			unset($bufStart, $bufEnd);
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
	'#(static public \\$unicodeProps)([^;]*);#',
	'$1' . str_replace('\\', '\\\\', $php) . ';',
	$file
);

file_put_contents(__DIR__ . '/../src/TextFormatter/JSParserGenerator.php', $file);

echo "Done.\n";