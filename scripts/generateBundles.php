#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$bundlesDir = __DIR__ . '/../src/Bundles';

foreach (glob(__DIR__ . '/../src/Configurator/Bundles/*.php') as $filepath)
{
	$bundleName = basename($filepath, '.php');
	$bundleDir  = $bundlesDir . '/' . $bundleName;
	if (!file_exists($bundleDir))
	{
		mkdir($bundleDir);
	}

	$className  = 's9e\\TextFormatter\\Configurator\\Bundles\\' . $bundleName;
	$configurator = $className::getConfigurator();

	$rendererGenerator = $configurator->rendering->setEngine('PHP');
	$rendererGenerator->className = 's9e\\TextFormatter\\Bundles\\' . $bundleName . '\\Renderer';
	$rendererGenerator->filepath  = $bundleDir . '/Renderer.php';

	$configurator->saveBundle(
		's9e\\TextFormatter\\Bundles\\' . $bundleName,
		$bundlesDir . '/' . $bundleName . '.php',
		['autoInclude' => false] + $className::getOptions()
	);
}

die("Done.\n");