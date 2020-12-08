#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->registeredVars['cacheDir'] = __DIR__ . '/../tests/.cache';

if (isset($_SERVER['argv'][2]))
{
	$configurator->rendering->parameters['MEDIAEMBED_THEME'] = $_SERVER['argv'][2];
}

$siteId = $_SERVER['argv'][1];
$configurator->MediaEmbed->add($siteId);

$dirpath  = realpath(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/sites');
$siteFile = $dirpath . '/' . $siteId . '.xml';

extract($configurator->finalize());

$html = "\n";
$site = simplexml_load_file($siteFile);
foreach ($site->example as $example)
{
	$html .= $renderer->render($parser->parse($example)) . "\n";
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