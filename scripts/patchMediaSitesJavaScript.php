#!/usr/bin/php
<?php

$compilerPath = __DIR__ . '/../vendor/node_modules/google-closure-compiler-linux/compiler';
if (!file_exists($compilerPath))
{
	echo "Cannot find $compilerPath\n";
	exit(1);
}
$tmpFile = sys_get_temp_dir() . '/onload.js';

$cmd = escapeshellcmd(realpath($compilerPath))
     . ' --formatting SINGLE_QUOTES'
     . ' --js_output_file ' . escapeshellarg($tmpFile)
     . ' --jscomp_error "*"'
     . ' --jscomp_off "strictCheckTypes"'
;

$hints = [
	'HAS_METHOD_FORWARD'       => '(:\\s*["\']forward)',
	'HAS_METHOD_RESIZE'        => '(:\\s*["\']resize)',
	'HAS_METHOD_RESIZE_HEIGHT' => '(height:(?![^<]++</style>))',
	'HAS_METHOD_RESIZE_WIDTH'  => '(width:(?![^<]++</style>))'
];

foreach (glob(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/sites/*.xml') as $filepath)
{
	$old = file_get_contents($filepath);
	if (!str_contains($old, 'onload'))
	{
		continue;
	}
	if (!preg_match('(//s9e\\.github\\.io/iframe/(\\d+)/(\\w+\\.min\\.html))', $old, $m))
	{
		continue;
	}

	$apiVersion = $m[1];
	$filename   = $m[2];

	if ($apiVersion !== '3')
	{
		echo "Skip {$m[0]}\n";
		continue;
	}

	$iframePath = __DIR__ . '/../ext/s9e.github.io/iframe/' . $apiVersion . '/' . $filename;
	if (!file_exists($iframePath))
	{
		echo "Cannot find $iframePath, fall back to ";
		$iframePath = 'https://s9e.github.io/iframe/' . $apiVersion . '/' . $filename;
		echo "$iframePath\n";
	}

	$exec   = $cmd;
	$iframe = file_get_contents($iframePath);
	$jsFile = __DIR__ . '/../src/Plugins/MediaEmbed/iframe-api-v' . $apiVersion . '.js';
	$js     = file_get_contents($jsFile);
	foreach ($hints as $name => $regexp)
	{
		$exec .= ' --define ' . $name . '=' . (preg_match($regexp, $iframe) ? 'true' : 'false');
//		$js = preg_replace('(' . $name . '\\s*=\\s*\\K[^;]++)', 'false', $js);
	}
	$exec .= ' --js ' . escapeshellarg($jsFile);

	exec($exec, $return);

	var_dump($return);


	die($exec);

	if ($new !== $old)
	{
		file_put_contents($filepath, $new);
		echo "Patched $filepath\n";
	}
}

die("Done.\n");