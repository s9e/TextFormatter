#!/usr/bin/php
<?php

foreach (glob(__DIR__ . '/../docs/examples/*.php') as $filepath)
{
	if (strpos($filepath, 'benchmark'))
	{
		continue;
	}

	$output = [];
	exec('php ' . escapeshellarg($filepath), $output);

	$php = file_get_contents($filepath);
	$php = preg_replace('!\\s*(?>//.*\\n)*$!', '', $php) . "\n\n// Outputs:\n//\n// " . implode("\n// ", $output) . "\n";

	file_put_contents($filepath, $php);
}

die("Done.\n");