#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('MediaEmbed', ['captureURLs' => false]);
$configurator->registeredVars['cacheDir'] = __DIR__ . '/../tests/.cache';

$sites = simplexml_load_file(__DIR__ . '/../src/s9e/TextFormatter/Plugins/MediaEmbed/Configurator/sites.xml');

foreach ($sites->site as $site)
{
	$configurator->MediaEmbed->add($site['id']);
}

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$siteHtml = [];
foreach ($sites->site as $site)
{
	if (isset($_SERVER['argv'][1]) && $site['id'] != $_SERVER['argv'][1])
	{
		continue;
	}

	foreach ($site->example as $example)
	{
		$text = '[media=' . $site['id'] . ']' . $example . '[/media]';

		$xml  = $parser->parse($text);
		$html = $renderer->render($xml);

		$siteHtml[(string) $site->name][$html] = 1;
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
	$out .= '<h2>' . $site . "</h2>\n" . implode("\n", array_keys($renders)) . "\n";
}
$out .= '</body></html>';

file_put_contents('/tmp/MediaEmbed.html', $out);

die("Done.\n");