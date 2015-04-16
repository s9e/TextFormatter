#!/usr/bin/php
<?php

include __DIR__ . '/../src/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('MediaEmbed', ['captureURLs' => false]);
$configurator->registeredVars['cacheDir'] = __DIR__ . '/../tests/.cache';

$dirpath = realpath(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/sites');
foreach (glob($dirpath . '/*.xml') as $siteFile)
{
	$siteId = basename($siteFile, '.xml');

	if (isset($_SERVER['argv'][1]) && $siteId != $_SERVER['argv'][1])
	{
		continue;
	}

	$configurator->MediaEmbed->add($siteId);
}

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$siteHtml = [];
foreach (glob($dirpath . '/*.xml') as $siteFile)
{
	$siteId = basename($siteFile, '.xml');

	if (isset($_SERVER['argv'][1]) && $siteId != $_SERVER['argv'][1])
	{
		continue;
	}

	$site = simplexml_load_file($siteFile);
	foreach ($site->example as $example)
	{
		$text = '[media=' . $siteId . ']' . $example . '[/media]';

		$xml  = $parser->parse($text);
		$html = $renderer->render($xml);

		$siteHtml[(string) $site['name']][$html] = 1;
	}
}

$out = '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>MediaEmbed test page</title>
	<base href="http://localhost"/>
</head>
<body>
';
foreach ($siteHtml as $site => $renders)
{
	$out .= '<h2 onclick="var s=this.nextElementSibling.style;s.display=s.display==\'none\'?\'\':\'none\'">' . $site . "</h2>\n<div>" . implode("\n", array_keys($renders)) . "</div>\n";
}
$out .= '</body></html>';

file_put_contents('/tmp/MediaEmbed.html', $out);

die("Done.\n");