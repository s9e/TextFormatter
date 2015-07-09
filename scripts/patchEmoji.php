#!/usr/bin/php
<?php

const FE0F = "\xEF\xB8\x8F";

$url = 'http://www.unicode.org/Public/UNIDATA/EmojiSources.txt';

$allText = '';
$allXml  = '';
$allHtml = '';

preg_match_all('(^[0-9A-F ]++)m', wget($url), $matches);
foreach ($matches[0] as $i => $seq)
{
	$seq  = ltrim(strtr($seq, 'ABCDEF ', 'abcdef-'), 0);
	$utf8 = seqToUtf8($seq);

	// Ignore whitespace symbols
	if ($seq === '2002' || $seq === '2003' || $seq === '2005')
	{
		continue;
	}
	if ($utf8[0] < "\xF0")
	{
		$innerXml = $utf8;
	}
	else
	{
		$innerXml = '';
		foreach (explode('-', $seq) as $hex)
		{
			$innerXml .= '&#' . hexdec($hex) . ';';
		}
	}

	$allText .= $utf8;
	$allXml  .= '<EMOJI seq="' . $seq . '">' . $innerXml . '</EMOJI>';
	$allHtml .= '<img alt="' . $utf8 . '" class="Emoji twitter-emoji" draggable="false" src="//twemoji.maxcdn.com/36x36/' . $seq . '.png">';
}

$url = 'https://raw.githubusercontent.com/github/gemoji/master/db/emoji.json';
$map = [];
foreach (json_decode(wget($url)) as $entry)
{
	if (!isset($entry->emoji))
	{
		continue;
	}
	foreach ($entry->aliases as $alias)
	{
		$utf8 = $entry->emoji;
		$seq = utf8ToSeq($utf8);
		$map[$alias] = $seq;

		$allText .= ':' . $alias . ':';
		$allXml  .= '<EMOJI seq="' . $seq . '">:' . $alias . ':</EMOJI>';
		$allHtml .= '<img alt=":' . $alias . ':" class="Emoji twitter-emoji" draggable="false" src="//twemoji.maxcdn.com/36x36/' . $seq . '.png">';
	}
}

file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.txt',  $allText);
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.xml',  '<r>' . $allXml . '</r>');
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.html', $allHtml);

$php = '[';
ksort($map);
foreach ($map as $alias => $seq)
{
	$php .= var_export((string) $alias, true) . '=>' . var_export($seq, true) . ',';
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
		file_get_contents($filepath)
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
		file_get_contents($filepath)
	)
);

die("Done.\n");

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
	foreach (explode('-', $seq) as $cp)
	{
		$str .= utf8(hexdec($cp));
	}

	return $str;
}

function utf8($cp)
{
	if ($cp < 0x80)
	{
		return chr($cp);
	}
	if ($cp < 0x800)
	{
		return chr(0b11000000 | ($cp >> 6))
		     . chr(0b10000000 | ($cp & 0b111111));
	}
	if ($cp < 0x10000)
	{
		return chr(0b11100000 | ($cp  >> 12))
		     . chr(0b10000000 | (($cp >> 6) & 0b111111))
		     . chr(0b10000000 | ($cp        & 0b111111));
	}

	return chr(0b11110000 | ($cp  >> 18))
	     . chr(0b10000000 | (($cp >> 12) & 0b111111))
	     . chr(0b10000000 | (($cp >> 6)  & 0b111111))
	     . chr(0b10000000 | ($cp         & 0b111111));
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