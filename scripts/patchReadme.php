#!/usr/bin/php
<?php

$composer = json_decode(file_get_contents(__DIR__ . '/../composer.lock'), true);
$packages = [];
foreach ($composer['packages-dev'] as $package)
{
	$packages[$package['name']] = $package;
}

$path = realpath(__DIR__ . '/../README.md');
$old  = file_get_contents($path);
$new  = preg_replace_callback(
	'(\\[(?<name>[-\\w]++/[-\\w]++)\\]\\(\\K[^\\)]++\\) v?[\\d.]++)',
	function ($m) use ($packages)
	{
		if (!isset($packages[$m['name']]))
		{
			return $m[0];
		}

		$package = $packages[$m['name']];

		return $package['homepage'] . ') ' . ltrim($package['version'], 'v');
	},
	$old
);

if ($new !== $old)
{
	file_put_contents($path, $new);
	echo "Replaced $path\n";
}
die("Done.\n");
