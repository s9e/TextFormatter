#!/usr/bin/php
<?php

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Items\UnsafeTemplate;
use s9e\TextFormatter\Tests\Plugins\BBCodes\BBCodesTest;

class PHPUnit_Framework_TestCase {}

include __DIR__ . '/../tests/bootstrap.php';

$dataDir      = realpath(__DIR__ . '/../tests/Configurator/RendererGenerators/data');
$pluginsDir   = $dataDir . '/Plugins';
$edgeCasesDir = $dataDir . '/EdgeCases';
$voidTestsDir = $dataDir . '/VoidTests';

// Generate edge-cases test data
foreach (glob($edgeCasesDir . '/e*.txt') as $filepath)
{
	$original = file_get_contents($filepath);

	$configurator = new s9e\TextFormatter\Configurator;
	$configurator->BBCodes->repositories->add('custom', $edgeCasesDir . '/repository.xml');

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

	$basename = substr($filepath, 0, -4);

	$xml  = $configurator->getParser()->parse($original);
	file_put_contents($basename . '.xml', $xml);

	$xsl  = $configurator->stylesheet->get();
	$html = $configurator->getRenderer()->render($xml);
	file_put_contents($basename . '.html.xsl', $xsl);
	file_put_contents($basename . '.html', $html);

	$configurator->stylesheet->setOutputMethod('xml');
	$xsl   = $configurator->stylesheet->get();
	$xhtml = $configurator->getRenderer()->render($xml);
	file_put_contents($basename . '.xhtml.xsl', $xsl);
	file_put_contents($basename . '.xhtml', $xhtml);
}

// Destroy then regenerate Plugins test data
array_map('unlink', glob($pluginsDir . '/*/*'));
array_map('rmdir', glob($pluginsDir . '/*'));

foreach (glob(__DIR__ . '/../tests/Plugins/*', GLOB_ONLYDIR) as $dirpath)
{
	$pluginName = basename($dirpath);
	$className  = 's9e\\TextFormatter\\Tests\\Plugins\\' . $pluginName . '\\ParserTest';
	$test       = new $className;

	if (!method_exists($test, 'getRenderingTests'))
	{
		continue;
	}

	// Create a directory for this plugin's data
	$pluginDir = $pluginsDir . '/' . $pluginName;
	mkdir($pluginDir);

	foreach ($test->getRenderingTests() as $case)
	{
		$original      = $case[0];
		$expected      = $case[1];
		$pluginOptions = (isset($case[2])) ? $case[2] : [];
		$setup         = (isset($case[3])) ? $case[3] : null;

		$configurator = new Configurator;
		$plugin = $configurator->plugins->load($pluginName, $pluginOptions);

		if ($setup)
		{
			call_user_func($setup, $configurator, $plugin);
		}

		$xml  = $configurator->getParser()->parse($original);
		$xsl  = $configurator->stylesheet->get();
		$html = $configurator->getRenderer()->render($xml);

		$filepath = $pluginDir . '/' . sprintf('%08X', crc32($xml));
		file_put_contents($filepath . '.xml', $xml);
		file_put_contents($filepath . '.html.xsl', $xsl);
		file_put_contents($filepath . '.html', $html);
	}
}

// Generate BBCodes test data
$pluginDir = $pluginsDir . '/BBCodes';
mkdir($pluginDir);

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

	$filepath = $pluginDir . '/' . sprintf('%08X', crc32($xml));
	file_put_contents($filepath . '.xml', $xml);
	file_put_contents($filepath . '.html.xsl', $xsl);
	file_put_contents($filepath . '.html', $html);
}

// Generate void/empty elements test data
$void = [
	'Void' => [
		'hr',
		'',
	],
	'NotVoid' => [
		'div',
		''
	],
	'MaybeVoidActuallyVoid' => [
		'{@name}',
		' name="hr"'
	],
	'MaybeVoidActuallyNotVoid' => [
		'{@name}',
		' name="div"'
	]
];

$empty = [
	'Empty'
		=> '',
	'NotEmpty'
		=> 'foo',
	'MaybeEmptyActuallyEmpty'
		=> '<xsl:apply-templates/>',
	'MaybeEmptyActuallyNotEmpty'
		=> '<xsl:value-of select="@name"/>'
];

foreach ($void as $voidType => $case)
{
	list($elName, $attribute) = $case;

	foreach ($empty as $emptyType => $content)
	{
		$basename = "$voidTestsDir/$voidType$emptyType";

		$xml = '<rt><FOO' . $attribute . '/></rt>';
		file_put_contents("$basename.xml", $xml);

		foreach (['html' => 'html', 'xml' => 'xhtml'] as $mode => $ext)
		{
			$configurator = new Configurator;
			$configurator->stylesheet->outputMethod = $mode;

			$configurator->tags->add('FOO')->defaultTemplate = new UnsafeTemplate(
				'<xsl:element name="' . $elName . '"><xsl:attribute name="id">foo</xsl:attribute>' . $content . '</xsl:element>'
			);

			file_put_contents("$basename.$ext.xsl", $configurator->stylesheet->get());
			file_put_contents("$basename.$ext", $configurator->getRenderer('XSLT')->render($xml));
		}
	}
}
