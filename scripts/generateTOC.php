#!/usr/bin/php
<?php

function addDir($root, $level = 0)
{
	$root = realpath($root);
	$out  = '';

	$prepend = (!$level) ? "\n\n### " : "\n" . str_repeat('  ', $level) . '* ';

	foreach (glob($root . '/*', GLOB_ONLYDIR) as $dirpath)
	{
		$basename = basename($dirpath);
		$out .= $prepend . '**' . $basename . '**' . addDir($dirpath, $level + 1);
	}

	foreach (glob($root . '/*.md') as $filepath)
	{
		if (substr($filepath, -9) === 'README.md')
		{
			continue;
		}

		if (preg_match('/^#+\\s*(.*)/', file_get_contents($filepath), $m))
		{
			$url = str_replace(
				realpath(__DIR__ . '/../'),
				'https://github.com/s9e/TextFormatter/blob/master',
				$filepath
			);

			$out .= $prepend . '[' . str_replace('[', '\\[', $m[1]) . '](' . $url . ')';
		}
	}

	return $out;
}

file_put_contents(
	__DIR__ . '/../docs/Cookbook/README.md',
	'## Table of content' . addDir(__DIR__ . '/../docs/Cookbook/')
);

die("Done.\n");