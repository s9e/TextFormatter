#!/usr/bin/php
<?php

include __DIR__ . '/../../vendor/autoload.php';

$configurator = new s9e\TextFormatter\Configurator;
foreach (glob(realpath(__DIR__ . '/../../src/Plugins') . '/*', GLOB_ONLYDIR) as $dirpath)
{
	$pluginName = basename($dirpath);
	$js = $configurator->$pluginName->getJSParser();

	$php = '
	public function getJSParser()
	{
		return ' . var_export($js, true) . ';
	}
';

	$filepath = $dirpath . '/Configurator.php';
	$file = file_get_contents($filepath);
	$pos = strrpos($file, '}');
	$file = substr($file, 0, $pos) . $php . substr($file, $pos);
	file_put_contents($filepath, $file);
	unlink($dirpath . '/Parser.js');
	echo "Removed $dirpath/Parser.js\n";
}

die("Done.\n");