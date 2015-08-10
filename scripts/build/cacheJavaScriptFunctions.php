#!/usr/bin/php
<?php

include __DIR__ . '/../../src/autoloader.php';

$cache = array();
foreach (glob(realpath(__DIR__ . '/../../src/Configurator/JavaScript/functions') . '/*.js') as $filepath)
{
	$cache[basename($filepath, '.js')] = file_get_contents($filepath);
	unlink($filepath);
	echo "Removed $filepath\n";
}

$php = '[';
ksort($cache);
foreach ($cache as $k => $v)
{
	$php .= "\n\t\t" . var_export($k, true) . '=>' . var_export($v, true) . ',';
}
$php = substr($php, 0, -1) . "\n\t]";

$filepath = realpath(__DIR__ . '/../../src/Configurator/JavaScript/FunctionProvider.php');
$old = file_get_contents($filepath);
$new = str_replace('public static $cache = []', 'public static $cache = ' . $php, $old);
if ($new !== $old)
{
	file_put_contents($filepath, $new);
	echo "Replaced $filepath\n";
}

die("Done.\n");