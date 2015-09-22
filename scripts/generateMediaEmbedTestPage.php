#!/usr/bin/php
<?php

include __DIR__ . '/../src/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->plugins->load('MediaEmbed', ['captureURLs' => false]);
$configurator->registeredVars['cacheDir'] = __DIR__ . '/../tests/.cache';

$siteId = $_SERVER['argv'][1];
$configurator->MediaEmbed->add($siteId);

$dirpath  = realpath(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/sites');
$siteFile = $dirpath . '/' . $siteId . '.xml';

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

$html = '';
$site = simplexml_load_file($siteFile);
foreach ($site->example as $example)
{
	$text  = '[media=' . $siteId . ']' . $example . '[/media]';
	$xml   = $parser->parse($text);
	$html .= $renderer->render($xml) . "\n";
}

$out = '<!DOCTYPE html>
<html>
<head>
	<meta charset="utf-8" />
	<title>MediaEmbed test page</title>
	<base href="http://localhost"/>
</head>
<body>' . $html . '</body></html>';

file_put_contents('/tmp/MediaEmbed.html', $out);

die("Done.\n");