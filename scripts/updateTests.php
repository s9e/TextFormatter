#!/usr/bin/php
<?php

function patchDir($dirpath)
{
	$dirpath = realpath($dirpath);
	array_map('patchDir',  glob($dirpath . '/*', GLOB_ONLYDIR));
	array_map('patchFile', glob($dirpath . '/*.php'));
}

function patchFile($filepath)
{
	$old = file_get_contents($filepath);

	$file = $old;
	$file = replaceTestCaseSignature($file);
	$file = replaceExpectedExceptionAnnotations($file);

	if ($file !== $old)
	{
		echo "Patched $filepath\n";
		file_put_contents($filepath, $file);
	}
}

function replaceTestCaseSignature($php)
{
	$php = preg_replace(
		'(public static function (setUpBefore|tearDownAfter)Class\\(\\)$)m',
		'$0: void',
		$php
	);
	$php = preg_replace(
		'(p\w+ function (setUp|tearDown)\\(\\)$)m',
		'protected function $1(): void',
		$php
	);

	return $php;
}

function replaceExpectedExceptionAnnotations($php)
{
	$php = preg_replace(
		'(^\\s+\\*\\s+@expectedException[^{]++\\{)m',
		"\$0\n",
		$php
	);
	$php = preg_replace_callback(
		'(^\\s+\\*\\s+@expectedExceptionMessage\\s+(\\N++)\\n([^{]++\\{))m',
		function ($m)
		{
			return $m[2] . "\n\t\t\$this->expectExceptionMessage(" . quote($m[1]) . ');';
		},
		$php
	);
	$php = preg_replace_callback(
		'(^\\s+\\*\\s+@expectedException\\s+(\\S++)(?: (\\N++))?\\n([^{]++\\{))m',
		function ($m)
		{
			$php = $m[3] . "\n\t\t\$this->expectException(" . quote($m[1]) . ');';
			if (!empty($m[2]))
			{
				$php .= "\n\t\t\$this->expectExceptionMessage(" . quote($m[2]) . ');';
			}

			return $php;
		},
		$php
	);

	return $php;
}

function quote($str)
{
	$single = "'" . strtr($str, ['\\' => '\\\\', "'" => "\\'"]) . "'";
	$double = '"' . strtr($str, ['\\' => '\\\\', '"' => '\\"', '$' => '\\$']) . '"';

	return (strlen($double) < strlen($single)) ? $double : $single;
}

patchDir(__DIR__ . '/../tests');

die("Done.\n");