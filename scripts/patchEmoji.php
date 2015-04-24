#!/usr/bin/php
<?php

const FE0F = "\xEF\xB8\x8F";

$filepath = sys_get_temp_dir() . '/emoji.json';
if (!file_exists($filepath))
{
	copy('https://github.com/Ranks/emojione/raw/master/emoji.json', $filepath);
}

$all = [];
$map = [];
foreach (json_decode(file_get_contents($filepath), true) as $shortname => $info)
{
	$seq  = $info['unicode'];
	$utf8 = seqToUtf8($seq);

	$all[$utf8] = $seq;
	$all[$info['shortname']] = $seq;

	if (isset($info['alternates']))
	{
		foreach ($info['alternates'] as $alternate)
		{
			$all[$alternate] = $seq;
		}
	}
}

$allText = '';
$allXml  = '';
$allHtml = '';
foreach ($all as $match => $seq)
{
	$html = '<img alt="' . $match . '" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/' . $seq . '.png">';

	if ($match[0] < "\xF0")
	{
		$content = $match;
	}
	else
	{
		$content = '';
		foreach (explode('-', $seq) as $hex)
		{
			$content .= '&#' . hexdec($hex) . ';';
		}
	}

	$allText .= $match . "\n";
	$allXml  .= '<E1 seq="' . $seq . '">' . $content . "</E1>\n";
	$allHtml .= $html . "\n";

	$map[str_replace(FE0F, '', $match)] = $seq;
}
ksort($map);

// PHP Parser
$arr = [];
foreach ($map as $k => $v)
{
	$arr[] = var_export($k, true) . '=>' . var_export($v, true);
}
$php = '[' . implode(',', $arr) . ']';

$filepath = __DIR__ . '/../src/Plugins/Emoji/Parser.php';
$file = file_get_contents($filepath);
if (!preg_match('((.*\\$map = ).*?(;\\n.*))s', $file, $m))
{
	die("Could not find \$map\n");
}
$file = $m[1] . $php . $m[2];
file_put_contents($filepath, $file);

// JS Parser
$arr = [];
foreach ($map as $k => $v)
{
	$arr[] = json_encode($k) . ':' . json_encode($v);
}
$js = '{' . implode(',', $arr) . '}';

$filepath = __DIR__ . '/../src/Plugins/Emoji/Parser.js';
$file = file_get_contents($filepath);
if (!preg_match('((.*\\map = ).*?(;\\n.*))s', $file, $m))
{
	die("Could not find map\n");
}
$file = $m[1] . $js . $m[2];
file_put_contents($filepath, $file);

file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.txt',  $allText);
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.xml',  '<r>' . $allXml . '</r>');
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.html', $allHtml);

die("Done.\n");

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
	$seq = str_replace('-FE0F', '', $seq);
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