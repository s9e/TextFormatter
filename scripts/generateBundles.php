#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

$bundlesDir = __DIR__ . '/../src/s9e/TextFormatter/Bundles';

foreach (glob(__DIR__ . '/../src/s9e/TextFormatter/Configurator/Bundles/*.php') as $filepath)
{
	$bundleName = basename($filepath, '.php');
	$bundleDir  = $bundlesDir . '/' . $bundleName;
	if (!file_exists($bundleDir))
	{
		mkdir($bundleDir);
	}

	$className  = 's9e\\TextFormatter\\Configurator\\Bundles\\' . $bundleName;
	$configurator = $className::getConfigurator();

	$rendererGenerator = $configurator->setRendererGenerator('PHP');
	$rendererGenerator->useMultibyteStringFunctions = false;
	$rendererGenerator->forceEmptyElements = false;
	$rendererGenerator->className = 's9e\\TextFormatter\\Bundles\\' . $bundleName . '\\Renderer';
	$rendererGenerator->filepath  = $bundleDir . '/Renderer.php';

	$configurator->saveBundle(
		's9e\\TextFormatter\\Bundles\\' . $bundleName,
		$bundlesDir . '/' . $bundleName . '.php',
		['autoInclude' => false]
	);
}

die("Done.\n");