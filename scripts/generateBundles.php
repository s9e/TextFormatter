#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

foreach (glob(__DIR__ . '/../src/s9e/TextFormatter/Configurator/Bundles/*.php') as $filepath)
{
	$bundleName = basename($filepath, '.php');
	$className  = 's9e\\TextFormatter\\Configurator\\Bundles\\' . $bundleName;

	$configurator = $className::getConfigurator();

	$configurator->setRendererGenerator(
		'PHP',
		's9e\\TextFormatter\\Bundles\\' . $bundleName . '\\Renderer',
		__DIR__ . '/../src/s9e/TextFormatter/Bundles/' . $bundleName . '/Renderer.php'
	);

	$configurator->saveBundle(
		's9e\\TextFormatter\\Bundles\\' . $bundleName,
		__DIR__ . '/../src/s9e/TextFormatter/Bundles/' . $bundleName . '.php'
	);
}

die("Done.\n");