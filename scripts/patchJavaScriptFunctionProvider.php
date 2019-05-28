#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$cache = array();
foreach (glob(realpath(__DIR__ . '/../src/Configurator/JavaScript/functions') . '/*.js') as $filepath)
{
	$cache[basename($filepath, '.js')] = file_get_contents($filepath);
}

$filepath = realpath(__DIR__ . '/../src/Configurator/JavaScript/FunctionProvider.php');
$old = file_get_contents($filepath);
$new = preg_replace_callback(
	'((public static \\$cache = \\[).*?(\\n\\t\\]))s',
	function ($m) use ($cache)
	{
		ksort($cache);
		$php = $m[1];
		foreach ($cache as $k => $v)
		{
			$php .= "\n\t\t" . var_export($k, true) . ' => ' . var_export($v, true) . ',';
		}
		$php = substr($php, 0, -1) . $m[2];

		return $php;
	},
	$old
);
if ($new !== $old)
{
	file_put_contents($filepath, $new);
	echo "Replaced $filepath\n";
}

die("Done.\n");