#!/usr/bin/php
<?php

use Emojione\Emojione;

const FE0F = "\xEF\xB8\x8F";

include __DIR__ . '/../src/autoloader.php';

$filepath = sys_get_temp_dir() . '/Emojione.php';
if (!file_exists($filepath))
{
	copy('https://github.com/Ranks/emojione/raw/master/lib/php/src/Emojione.php', $filepath);
}
include $filepath;

foreach (Emojione::$unicode_replace as $variant => $shortcode)
{
	if (strpos($variant, FE0F) === false)
	{
		continue;
	}

	$original = str_replace(FE0F, '', $variant);

	if (!isset(Emojione::$unicode_replace[$original]))
	{
		echo 'Unknown variant ', bin2hex($variant), "\n";
	}
	elseif (Emojione::$unicode_replace[$original] !== $shortcode)
	{
		echo 'Variant ', bin2hex($variant), ' (', $shortcode, ' does not match ', bin2hex($original), '(', Emojione::$unicode_replace[$original], ")\n";
	}
}

$map = [];
$variants = [];
$allText = '';
$allHtml = '';

foreach (Emojione::$unicode_replace as $utf8 => $shortcode)
{
	if (!preg_match('(^:[-+\\w]+:$)', $shortcode))
	{
		die("Unexpected shortcode $shortcode\n");
	}

	if (!isset(Emojione::$shortcode_replace[$shortcode]))
	{
		die("Missing Emojione shortcode $shortcode\n");
	}

	$html = '<img alt="' . $utf8 . '" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/' . strtoupper(Emojione::$shortcode_replace[$shortcode]) . '.png">';

	$allText .= $utf8;
	$allHtml .= $html;

	$utf8 = str_replace(FE0F, '', $utf8);

	$hex = [];
	preg_match_all('(.)us', $utf8, $m);
	foreach ($m[0] as $str)
	{
		$hex[] = sprintf('%04X', cp($str));
	}

	$imgName = implode('-', $hex);

	if ($imgName !== strtoupper(Emojione::$shortcode_replace[$shortcode]))
	{
		echo "$shortcode $imgName does not match ", Emojione::$shortcode_replace[$shortcode], "\n";
	}

	$map[$shortcode] = $map[$utf8] = $imgName;
}
ksort($map);

// PHP Parser
$arr = [];
foreach ($map as $k => $v)
{
	if (preg_match('([^ -\\x7E])', $k))
	{
		$k = preg_replace_callback(
			'([^ -\\x7E])',
			function ($m)
			{
				return '\\x' . strtoupper(dechex(ord($m[0])));
			},
			$k
		);

		$k = '"' . $k . '"';
	}
	else
	{
		$k = var_export($k, true);
	}

	$arr[] = $k . '=>' . var_export($v, true);
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