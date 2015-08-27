#!/usr/bin/php
<?php

$filepath = __DIR__ . '/../../composer.json';
$file     = file_get_contents($filepath);

$regexp = '(("version":\\s*")([^"]+))';
if (!preg_match($regexp, $file, $m))
{
	echo "Cannot find version in $filepath\n";
	exit(1);
}

if (empty($_SERVER['argv'][1]))
{
	echo "Version number missing\n";
	exit(1);
}

switch ($_SERVER['argv'][1])
{
	case 'dev':
		$p = explode('.', $m[2]);
		++$p[2];

		$newVersion = implode('.', $p) . '-dev';
		break;

	case 'release':
		$newVersion = str_replace('-dev', '', $m[2]);
		break;

	default:
		$newVersion = $_SERVER['argv'][1];
}

file_put_contents($filepath, preg_replace($regexp, '${1}' . $newVersion, $file));

die($newVersion);