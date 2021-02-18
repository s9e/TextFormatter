#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->registeredVars['cacheDir'] = __DIR__ . '/../tests/.cache';

if (isset($_SERVER['argv'][2]))
{
	$configurator->rendering->parameters['MEDIAEMBED_THEME'] = $_SERVER['argv'][2];
}

$siteId     = $_SERVER['argv'][1];
$siteConfig = $configurator->MediaEmbed->defaultSites[$siteId];
$configurator->MediaEmbed->add($siteId);

$isAmp = (isset($_SERVER['argv'][2]) && $_SERVER['argv'][2] === 'amp');
$head  = '';
if ($isAmp)
{
	$amp  = $siteConfig['amp'];
	$head = '<script async src="https://cdn.ampproject.org/v0.js"></script>
	<script custom-element="' . $amp['custom-element'] . '" src="' . $amp['src'] . '" async></script>';

	$configurator->tags[$siteId]->template = $amp['template'];
}

extract($configurator->finalize());

$html = "\n";
foreach ((array) $siteConfig['example'] as $example)
{
	$html .= $renderer->render($parser->parse($example)) . "\n";
}

$out = '<!DOCTYPE html>
<html' . ($isAmp ? " \u{26A1}" : '') . '>
<head>
	<meta charset="utf-8">
	<title>MediaEmbed test page</title>
	<base href="http://localhost">
	' . $head . '
</head>
<body>' . $html . '</body></html>';

file_put_contents('/tmp/MediaEmbed.html', $out);

die("Done.\n");