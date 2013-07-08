#!/usr/bin/php
<?php

$version = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : PHP_VERSION;

if (version_compare($version, '5.4.99', '>'))
{
	die('No need to run ' . __FILE__ . ' on PHP ' . PHP_VERSION . "\n");
}

function convertCustom($filepath, &$file)
{
	$replacements = array(
	);

	$filename = basename($filepath);
	if (isset($replacements[$filename]))
	{
		foreach ($replacements[$filename] as $pair)
		{
			list($search, $replace) = $pair;
			$file = str_replace($search, $replace, $file);
		}
	}
}

function convertForeachList($filepath, &$file)
{
	$file = preg_replace_callback(
		'#(\\s+as\\s+(?:\\$\\w+\\s*=>\\s*)?)(list\\([^)]+\\))(\\)\\s*\\{(\\s*))#',
		function ($m)
		{
			// Generate a var name based on replaced code
			$varName = '$_' . crc32($m[0]);

			return $m[1] . $varName . $m[3] . $m[2] . ' = ' . $varName . ';' . $m[4];
		},
		$file
	);
}

function convertFile($filepath)
{
	$file    = file_get_contents($filepath);
	$oldFile = $file;

	convertCustom($filepath, $file);
	convertForeachList($filepath, $file);

	if ($file !== $oldFile)
	{
		echo "Replacing $filepath\n";
		file_put_contents($filepath, $file);
	}
}

function convertDir($dir)
{
	foreach (glob($dir . '/*', GLOB_ONLYDIR) as $sub)
	{
		convertDir($sub);
	}

	foreach (glob($dir . '/*.php') as $filepath)
	{
		convertFile($filepath);
	}
}

convertDir(realpath(__DIR__ . '/../../src/s9e/TextFormatter'));
convertDir(realpath(__DIR__ . '/../../tests'));
