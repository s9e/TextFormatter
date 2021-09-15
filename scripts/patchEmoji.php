#!/usr/bin/php
<?php

$emojiVersion = '14.0';
$ucdVersion   = '14.0.0';

$emoji   = [];
$file    = wget('http://unicode.org/Public/' . $ucdVersion . '/ucd/emoji/emoji-data.txt');
$images  = codepointsFromFile($file, 'Emoji_Presentation');

foreach (codepointsFromFile($file, 'Emoji') as $cp)
{
	$utf8 = utf8($cp);
	if (!isset($images[$cp]))
	{
		// Append U+FE0F to emoji without Emoji_Presentation=Yes
		$utf8 .= "\xEF\xB8\x8F";
	}
	$emoji[] = $utf8;
}

$file  = wget('http://unicode.org/Public/emoji/' . $emojiVersion . '/emoji-sequences.txt');
$file .= wget('http://unicode.org/Public/emoji/' . $emojiVersion . '/emoji-zwj-sequences.txt');
preg_match_all('(^[0-9A-F ]+)m', $file, $matches);
foreach ($matches[0] as $seq)
{
	$utf8    = seqToUtf8(trim($seq));
	$emoji[] = $utf8;
}

// Add all possible flag combinations. It makes the regexp simpler and covers any missing flag
for ($i = 0x1F1E6; $i <= 0x1F1FF; ++$i)
{
	for ($j = 0x1F1E6; $j <= 0x1F1FF; ++$j)
	{
		$emoji[] = utf8($i) . utf8($j);
	}
}

$allText = "\n";
$allXml  = "<r>\n";
foreach ($emoji as $utf8)
{
	if (strpos($utf8, "\xE2") === false && strpos($utf8, "\xEF") === false && strpos($utf8, "\xF0") === false)
	{
		echo bin2hex($utf8), " does not contain 0xE2, 0xEF or 0xF0. Parser.php would need to be updated.\n";
	}
	$hex  = utf8ToHex($utf8);
	$seq  = removeMarks($hex);
	$tseq = ltrim($hex, '0');
	if (strpos($tseq, '-200d') === false)
	{
		$tseq = str_replace('-fe0f', '', $tseq);
	}

	$allText .= $utf8 . "\n";
	$allXml  .= '<EMOJI seq="' . $seq . '" tseq="' . $tseq . '">' . $utf8 . "</EMOJI>\n";

	$allText .= ':' . $hex . ":\n";
	$allXml  .= '<EMOJI seq="' . $seq . '" tseq="' . $tseq . '">:' . $hex . ":</EMOJI>\n";
}
$allXml .= '</r>';

include __DIR__ . '/../src/Utils.php';
$allXml = s9e\TextFormatter\Utils::encodeUnicodeSupplementaryCharacters($allXml);
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.txt',  $allText);
file_put_contents(__DIR__ . '/../tests/Plugins/Emoji/all.xml',  $allXml);

include __DIR__ . '/../vendor/autoload.php';

$filepath = realpath(__DIR__ . '/../src/Plugins/Emoji/Parser.php');
file_put_contents(
	$filepath,
	preg_replace_callback(
		'((protected \\$unicodeRegexp = ).*;)',
		function ($m) use ($emoji)
		{
			$builder = new s9e\RegexpBuilder\Builder([
				'input'  => 'Bytes',
				'output' => 'PHP'
			]);
			$regexp = '(' . $builder->build($emoji) . '(?!\\xEF\\xB8\\x8E)(?:\\xEF\\xB8\\x8F)?)S';

			return $m[1] . var_export($regexp, true) . ';';
		},
		file_get_contents($filepath)
	)
);

$filepath = realpath(__DIR__ . '/../src/Plugins/Emoji/Parser.js');
file_put_contents(
	$filepath,
	preg_replace_callback(
		'((var unicodeRegexp = ).*;)',
		function ($m) use ($emoji)
		{
			$builder = new s9e\RegexpBuilder\Builder([
				'input'         => 'Utf8',
				'inputOptions'  => ['useSurrogates' => true],
				'output'        => 'JavaScript',
				'outputOptions' => ['case' => 'lower']
			]);
			$regexp = '/' . $builder->build($emoji) . '(?!\\ufe0e)\\ufe0f?/g';

			return $m[1] . $regexp . ';';
		},
		file_get_contents($filepath)
	)
);

die("Done.\n");

function codepointsFromFile($file, $type)
{
	$regexp = '(^([0-9A-F]+)(\\..[0-9A-F]+)?\\s*;\\s*' . $type . '(?!\\w))m';
	preg_match_all($regexp, $file, $matches, PREG_SET_ORDER);

	$codepoints = [];
	foreach ($matches as $m)
	{
		$start = hexdec($m[1]);
		$end   = (empty($m[2])) ? $start : hexdec(ltrim($m[2], '.'));
		$cp    = $start;
		do
		{
			$codepoints[$cp] = $cp;
		}
		while (++$cp <= $end);
	}

	return $codepoints;
}

function wget($url, $prefix = '')
{
	$filepath = sys_get_temp_dir() . '/' . $prefix . basename($url);
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

function utf8ToHex($str)
{
	$hex = [];
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
		$hex[] = sprintf('%04x', $cp);
	}
	while (++$i < strlen($str));

	return implode('-', $hex);
}

function removeMarks($seq)
{
	$seq = str_replace('-fe0f', '', $seq);
	$seq = str_replace('-200d', '', $seq);

	return $seq;
}