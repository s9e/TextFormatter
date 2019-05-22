#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$html = [];
$html[] = '<table>';
$html[] = '	<tr>';
$html[] = '		<th>Id</th>';
$html[] = '		<th>Example URLs</th>';
$html[] = '	</tr>';

$configurator = new s9e\TextFormatter\Configurator;
$dirpath = realpath(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/sites');
foreach (glob($dirpath . '/*.xml') as $siteFile)
{
	$site   = simplexml_load_file($siteFile);
	$siteId = basename($siteFile, '.xml');

	$html[] = '	<tr title="' . $site['name'] . '">';
	$html[] = '		<td style="font-size:75%"><code>' . $siteId . '</code></td>';
	$html[] = '		<td style="font-size:50%">' . implode('<br/>', (array) $site->example) . '</td>';
	$html[] = '	</tr>';
}

$html[] = '</table>';

$filepath = __DIR__ . '/../docs/Plugins/MediaEmbed/Sites.md';
$file     = file_get_contents($filepath);
$pos      = strpos($file, '<table>');

if ($pos === false)
{
	die("Could not find table\n");
}

$file = substr($file, 0, $pos) . str_replace('&', '&amp;', implode("\n", $html));

file_put_contents($filepath, $file);

die("Done.\n");