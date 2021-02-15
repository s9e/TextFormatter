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

extract($configurator->finalize());

$html = "\n";
foreach ($configurator->MediaEmbed->defaultSites[$siteId]['example'] as $example)
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