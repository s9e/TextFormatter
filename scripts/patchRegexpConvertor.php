#!/usr/bin/php
<?php

use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;
include __DIR__ . '/../src/autoloader.php';

$filepath = __DIR__ . '/../src/Configurator/JavaScript/RegexpConvertor.php';

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

function wget($url)
{
	return file_get_contents(
		'compress.zlib://' . $url,
		false,
		stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']])
	);
}

if (!file_exists('/tmp/props.txt'))
{
	file_put_contents(
		'/tmp/props.txt',
		wget('http://unicode.org/Public/UNIDATA/PropList.txt') . "\n" . wget('http://unicode.org/Public/UNIDATA/DerivedCoreProperties.txt')
	);
}

$supportedProperties = array_flip([
	'L&', 'Ll', 'Lm', 'Lo', 'Lt', 'Lu',
	'Nd', 'Nl', 'No',
	'Pc', 'Pd', 'Pe', 'Pf', 'Pi', 'Po', 'Ps',
	'Sc', 'Sk', 'Sm', 'So',
	'Zl', 'Zp', 'Zs'
]);

$lines = file('/tmp/props.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

$ranges = [];
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

	$ranges['p' . $propName][$range] = false;
	$ranges['p' . $propName[0]][$range] = false;
}

unset($ranges['pL&']);

/**
* Sort ranges and create anti-ranges for \P properties
*/
foreach ($ranges as $propName => &$propRanges)
{
	ksort($propRanges, SORT_STRING);

	$nextCp = 0;
	$tmp = [];

	foreach ($propRanges as $range => $void)
	{
		$startCp = hexdec(substr($range, 0, 4));

		if ($startCp - 1 > $nextCp)
		{
			$tmp[sprintf('%04X%04X', $nextCp, $startCp - 1)] = false;
		}

		$nextCp = max($nextCp, 1 + hexdec(substr($range, 4)));
	}

	if ($nextCp <= 0xFFFF)
	{
		$tmp[sprintf('%04X%04X', $nextCp, 0xFFFF)] = false;
	}

	$propName[0] = 'P';
	$ranges[$propName] = $tmp;
}
unset($propRanges);

$props = [];
foreach ($ranges as $propName => $propRanges)
{
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

$php = " = [\n\t\t" . $php . "\n\t]";

$file = file_get_contents($filepath);
$file = preg_replace(
	'#(protected static \\$unicodeProps(?!Regexp))(.*?)\\n\\t[\\])];#s',
	'$1;',
	$file
);
$file = str_replace(
	'protected static $unicodeProps;',
	'protected static $unicodeProps' . $php . ';',
	$file
);


$propNames = [];
foreach (array_keys($props) as $propName)
{
	$propNames[] = $propName;
	$propNames[] = preg_replace('#(.)(.+)#', '$1{$2}', $propName);
	$propNames[] = preg_replace('#(.)(.+)#', '$1{^$2}', $propName);
}

$regexp = '((?<!\\\\)((?:\\\\\\\\)*+)\\\\(' . RegexpBuilder::fromList($propNames) . '))';
$file = preg_replace(
	'#(protected static \\$unicodePropsRegexp)[^;]++;#s',
	'$1;',
	$file
);
$file = str_replace(
	'protected static $unicodePropsRegexp;',
	'protected static $unicodePropsRegexp = ' . var_export($regexp, true) . ';',
	$file
);

file_put_contents($filepath, $file);

echo "Done.\n";