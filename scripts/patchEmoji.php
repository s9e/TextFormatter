#!/usr/bin/php
<?php

$url = 'https://raw.githubusercontent.com/github/gemoji/master/db/emoji.json';
$map = [];
foreach (json_decode(wget($url)) as $entry)
{
	if (!isset($entry->emoji))
	{
		continue;
	}
	$utf8 = str_replace("\xEF\xB8\x8F", '', $entry->emoji);
	$seq  = utf8ToSeq($utf8);
	foreach ($entry->aliases as $alias)
	{
		$map[$alias] = $seq;
	}
}

$allText = '';
$allXml  = '';

$imgEmoji  = ['1f1e6'];
$textEmoji = [];
$utf8Emoji = [];

$url = 'http://unicode.org/Public/emoji/1.0/emoji-data.txt';
$regexp = '(^([0-9A-F][0-9A-F ]+[0-9A-F])\\s*;\\s*(emoji|text)\\s*;\\s*L1\\s*;\\s*(?:none|primary|secondary))m';
preg_match_all($regexp, wget($url), $matches, PREG_SET_ORDER);
foreach ($matches as list(, $seq, $style))
{
	if ($seq >= '1F1E6 1F1E6' && $seq <= '1F1FF 1F1FF')
	{
		// Skip flags
		continue;
	}
	if ($seq >= '0023 20E3' && $seq <= '0039 20E3')
	{
		// Skip keypads
		continue;
	}

	$utf8 = seqToUtf8($seq);
	if ($style === 'emoji')
	{
		$imgEmoji[]  = $seq;
		$utf8Emoji[] = $utf8;

		$allText .= $utf8;
		$allXml  .= getXml($utf8, $seq);
	}
	else
	{
		$textEmoji[] = $seq;
		$utf8Emoji[] = $utf8 . "\xEF\xB8\x8F";

		$allText .= $utf8 . $utf8 . "\xEF\xB8\x8F";
		$allXml  .= $utf8 . getXml($utf8 . "\xEF\xB8\x8F", $seq);
	}
}

foreach ($utf8Emoji as $utf8)
{
	if (strpos($utf8, "\xE2") === false && strpos($utf8, "\xEF") === false && strpos($utf8, "\xF0") === false)
	{
		echo bin2hex($utf8), " does not match.\n";
	}
}

include __DIR__ . '/../src/Utils.php';
$allXml = s9e\TextFormatter\Utils::encodeUnicodeSupplementaryCharacters($allXml);
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.txt',  $allText);
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.xml',  '<r>' . $allXml . '</r>');

$php = '[';
ksort($map);
foreach ($map as $emoji => $seq)
{
	$php .= var_export((string) $emoji, true) . '=>' . var_export($seq, true) . ',';
}
$php = substr($php, 0, -1) . ']';

$filepath = realpath(__DIR__ . '/../src/Plugins/Emoji/Parser.php');
file_put_contents(
	$filepath,
	preg_replace_callback(
		'((protected static \\$map = ).*;)',
		function ($m) use ($php)
		{
			return $m[1] . $php . ';';
		},
		preg_replace_callback(
			'((protected \\$unicodeRegexp = ).*;)',
			function ($m) use ($imgEmoji, $textEmoji)
			{
				return $m[1] . var_export(getPHPRegexp($imgEmoji, $textEmoji), true) . ';';
			},
			file_get_contents($filepath)
		)
	)
);

$filepath = realpath(__DIR__ . '/../src/Plugins/Emoji/Parser.js');
file_put_contents(
	$filepath,
	preg_replace_callback(
		'((var map = ).*;)',
		function ($m) use ($map)
		{
			return $m[1] . json_encode($map) . ';';
		},
		preg_replace_callback(
			'((var unicodeRegexp = ).*;)',
			function ($m) use ($imgEmoji, $textEmoji)
			{
				return $m[1] . getJSRegexp($imgEmoji, $textEmoji) . ';';
			},
			file_get_contents($filepath)
		)
	)
);

die("Done.\n");

function getPHPRegexp($imgEmoji, $textEmoji)
{
	$regexp = '(';

	// Start with emoji as images
	$regexp .= getPHPRegexpSubpattern($imgEmoji);

	// Replace our single RIS emoji with the expression for a RIS pair (flag)
	$regexp = str_replace(
		'\\x87\\xA6',
		'\\x87[\\xA6-\\xBF]\\xF0\\x9F\\x87[\\xA6-\\xBF]',
		$regexp
	);

	// Not if followed by U+FE0E, optionally followed by U+FE0F
	$regexp .= '(?!\\xEF\\xB8\\x8E)(?:\\xEF\\xB8\\x8F)?';

	// Add emoji that require a variant selector
	$regexp .= '|' . getPHPRegexpSubpattern($textEmoji) . '\\xEF\\xB8\\x8F';

	// Finish with keypad emoji
	$regexp .= '|[#0-9](?:\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3)S';

	return $regexp;
}

function getPHPRegexpSubpattern(array $emoji)
{
	$emoji = array_map('seqToUtf8', $emoji);
	sort($emoji);
	$emoji = array_map(
		function ($utf8)
		{
			return array_map('ord', str_split($utf8, 1));
		},
		$emoji
	);

	$regexp = serializeTrie(
		buildTrie($emoji),
		function ($ord)
		{
			return '\\x' . strtoupper(dechex($ord));
		}
	);

	return $regexp;
}

function getJSRegexp($imgEmoji, $textEmoji)
{
	$regexp = '/';

	// Start with emoji as images
	$regexp .= getJSRegexpSubpattern($imgEmoji);

	// Insert the expression for a RIS pair (flag)
	$regexp = preg_replace(
		'(\\\\uD83C(\\[[^]]++\\]))',
		'\\uD83C(?:$1|[\\uDDE6-\\uDDFF]\\uD83C[\\uDDE6-\\uDDFF])',
		$regexp
	);

	// Not if followed by U+FE0E, optionally followed by U+FE0F
	$regexp .= '(?!\\uFE0E)\\uFE0F?';

	// Add emoji that require a variant selector
	$regexp .= '|' . getJSRegexpSubpattern($textEmoji) . '\\uFE0F';

	// Finish with keypad emoji
	$regexp .= '|[#0-9]\\uFE0F?\\u20E3/g';

	return $regexp;
}

function u($cp)
{
	return sprintf('\\u%04X', $cp);
}

function getJSRegexpSubpattern(array $emoji)
{
	// Remove the single RIS emoji
	$key = array_search('1f1e6', $emoji, true);
	if ($key !== false)
	{
		unset($emoji[$key]);
	}
	$emoji = array_map('hexdec', $emoji);
	sort($emoji);
	$arr = [];
	foreach ($emoji as $cp)
	{
		if ($cp < 0x10000)
		{
			$arr[''][] = $cp;
		}
		else
		{
			$key   = u(0xD7C0 + ($cp >> 10));
			$value = 0xDC00 + ($cp & 0x3FF);
			$arr[$key][] = $value;
		}
	}

	$branches = [];
	foreach ($arr as $key => $values)
	{
		$expr = '';
		foreach (getRanges($values) as list($start, $end))
		{
			$expr .= u($start);
			if ($end > $start)
			{
				if ($end > $start + 1)
				{
					$expr .= '-';
				}
				$expr .= u($end);
			}
		}
		if (count($values) > 1)
		{
			$expr = '[' . $expr . ']';
		}
		$branches[] = $key . $expr;
	}

	return (isset($branches[1])) ? '(?:' . implode('|', $branches) . ')' : $branches[0];
}

function buildTrie(array $values)
{
	$trie = [];
	foreach ($values as $ords)
	{
		$lastOrd = array_pop($ords);

		$ref =& $trie;
		foreach ($ords as $ord)
		{
			$ref =& $ref[$ord];
		}
		$ref[] = $lastOrd;
	}

	return $trie;
}

function serializeTrie(array $trie, callable $fn)
{
	$exprs = [];
	foreach ($trie as $ord => $sub)
	{
		$exprs[] = $fn($ord) . serializeSub($sub, $fn);
	}

	$expr = implode('|', $exprs);

	return (count($exprs) > 1) ? '(?:' . $expr . ')' : $expr;
}

function serializeSub(array $sub, callable $fn)
{
	if (is_array(end($sub)))
	{
		$expr = serializeTrie($sub, $fn);

		return $expr;
	}

	$expr = '';
	foreach (getRanges($sub) as list($start, $end))
	{
		$expr .= $fn($start);
		if ($end > $start)
		{
			if ($end > $start + 1)
			{
				$expr .= '-';
			}
			$expr .= $fn($end);
		}
	}

	return (count($sub) > 1) ? '[' . $expr . ']' : $expr;
}

function getRanges(array $values)
{
	$ranges = [];
	$i = count($values) - 1;
	while ($i >= 0)
	{
		$start = $i;
		$end   = $i;
		while ($start > 0 && $values[$start - 1] === $values[$end] - ($end + 1 - $start))
		{
			--$start;
		}
		$ranges[] = [$values[$start], $values[$end]];
		$i = $start - 1;
	}

	return array_reverse($ranges);
}

function getXml($utf8, $seq)
{
	return '<EMOJI seq="' . ltrim(strtolower($seq), '0') . '">' . $utf8 . '</EMOJI>';
}

function wget($url)
{
	$filepath = sys_get_temp_dir() . '/' . basename($url);
	if (!file_exists($filepath))
	{
		copy(
			'compress.zlib://' . $url,
			$filepath,
			stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']])
		);
	}

	return file_get_contents($filepath);
}

function cp($str)
{
	if (strlen($str) === 1)
	{
		$cp = ord($str);
	}
	elseif (strlen($str) === 2)
	{
		$cp = ((ord($str[0]) & 0b00011111) << 6)
			|  (ord($str[1]) & 0b00111111);
	}
	elseif (strlen($str) === 3)
	{
		$cp = ((ord($str[0]) & 0b00001111) << 12)
			| ((ord($str[1]) & 0b00111111) << 6)
			|  (ord($str[2]) & 0b00111111);
	}
	elseif (strlen($str) === 4)
	{
		$cp = ((ord($str[0]) & 0b00000111) << 18)
			| ((ord($str[1]) & 0b00111111) << 12)
			| ((ord($str[2]) & 0b00111111) << 6)
			|  (ord($str[3]) & 0b00111111);
	}
	else
	{
		die('Bad UTF-8? ' . bin2hex($str));
	}

	return $cp;
}

function seqToUtf8($seq)
{
	$str = '';
	foreach (preg_split('([-_ ])', $seq) as $cp)
	{
		$str .= utf8(hexdec($cp));
	}

	return $str;
}

function utf8($cp)
{
	return html_entity_decode('&#x' . dechex($cp) . ';', ENT_QUOTES, 'UTF-8');
}

function utf8ToSeq($str)
{
	// Remove U+FE0F from the emoji
	$str = str_replace("\xEF\xB8\x8F", '', $str);
	$seq = [];
	$i   = 0;
	do
	{
		$cp = ord($str[$i]);
		if ($cp >= 0b11110000)
		{
			$cp = (($cp & 7) << 18) | ((ord($str[++$i]) & 63) << 12) | ((ord($str[++$i]) & 63) << 6) | (ord($str[++$i]) & 63);
		}
		elseif ($cp >= 0b11100000)
		{
			$cp = (($cp & 15) << 12) | ((ord($str[++$i]) & 63) << 6) | (ord($str[++$i]) & 63);
		}
		elseif ($cp >= 0b11000000)
		{
			$cp = (($cp & 15) << 6) | (ord($str[++$i]) & 63);
		}
		$seq[] = dechex($cp);
	}
	while (++$i < strlen($str));

	return implode('-', $seq);
}