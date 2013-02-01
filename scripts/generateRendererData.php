#!/usr/bin/php
<?php

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Tests\Plugins\BBCodes\BBCodesTest;

class PHPUnit_Framework_TestCase {}

include __DIR__ . '/../tests/bootstrap.php';

$dir = __DIR__ . '/../tests/Configurator/RendererGenerators/data/';
/*
// Generate edge-cases test data
foreach (glob($dir . 'e*.txt') as $filepath)
{
	$original = file_get_contents($filepath);

	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->BBCodes->repositories->add('custom', $dir . 'repository.xml');

	preg_match_all('/\\[(\\w+)/', $original, $matches);

	foreach ($matches[1] as $bbcodeName)
	{
		if (isset($configurator->BBCodes[$bbcodeName]))
		{
			continue;
		}

		$configurator->BBCodes->addFromRepository(
			$bbcodeName,
			(preg_match('#^t\\d+#i', $bbcodeName)) ? 'custom' : 'default'
		);
	}

	$xml  = $configurator->getParser()->parse($original);
	file_put_contents(substr($filepath, 0, -3) . 'xml', $xml);

	$xsl  = $configurator->stylesheet->get();
	$html = $configurator->getRenderer()->render($xml);
	file_put_contents(substr($filepath, 0, -3) . 'html.xsl', $xsl);
	file_put_contents(substr($filepath, 0, -3) . 'html', $html);

	$configurator->stylesheet->setOutputMethod('xml');
	$xsl   = $configurator->stylesheet->get();
	$xhtml = $configurator->getRenderer()->render($xml);
	file_put_contents(substr($filepath, 0, -3) . 'xhtml.xsl', $xsl);
	file_put_contents(substr($filepath, 0, -3) . 'xhtml', $xhtml);
}

// Generate BBCodes test data
$test = new BBCodesTest;
foreach ($test->getPredefinedBBCodesTests() as $case)
{
	$original = $case[0];
	$expected = $case[1];
	$setup    = (isset($case[2])) ? $case[2] : null;

	$configurator = new Configurator;

	if (isset($setup))
	{
		call_user_func($setup, $configurator);
	}

	// Capture the names of the BBCodes used
	preg_match_all('/\\[([*\\w]+)/', $original, $matches);

	foreach ($matches[1] as $bbcodeName)
	{
		if (!isset($configurator->BBCodes[$bbcodeName]))
		{
			$configurator->BBCodes->addFromRepository($bbcodeName);
		}
	}

	$configurator->addHTML5Rules();

	$xml  = $configurator->getParser()->parse($original);
	$xsl  = $configurator->stylesheet->get();
	$html = $configurator->getRenderer()->render($xml);

	// XSLTProcessor does not correctly identify <embed> as a void element, which screws up the
	// default rendering. We correct the expected rendering manually
	$html = str_replace('</embed>', '', $html);

	$filepath = $dir . 'b' . sprintf('%08X', crc32($xml));
	file_put_contents($filepath . '.xml', $xml);
	file_put_contents($filepath . '.html.xsl', $xsl);
	file_put_contents($filepath . '.html', $html);
}
*/
// Generate Plugins test data
foreach (glob(__DIR__ . '/../tests/Plugins/*', GLOB_ONLYDIR) as $dirpath)
{
	$pluginName = basename($dirpath);
	$className  = 's9e\\TextFormatter\\Tests\\Plugins\\' . $pluginName . '\\ParserTest';
	$test       = new $className;

	if (!method_exists($test, 'getRenderingTests'))
	{
		continue;
	}

	foreach ($test->getRenderingTests() as $case)
	{
		$original      = $case[0];
		$expected      = $case[1];
		$pluginOptions = (isset($case[2])) ? $case[2] : array();
		$setup         = (isset($case[3])) ? $case[3] : null;

		$configurator = new Configurator;
		$configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			call_user_func($setup, $configurator);
		}

		$xml  = $configurator->getParser()->parse($original);
		$xsl  = $configurator->stylesheet->get();
		$html = $configurator->getRenderer()->render($xml);

		$filepath = $dir . $pluginName . '.' . sprintf('%08X', crc32($xml));
		file_put_contents($filepath . '.xml', $xml);
		file_put_contents($filepath . '.html.xsl', $xsl);
		file_put_contents($filepath . '.html', $html);
	}
}