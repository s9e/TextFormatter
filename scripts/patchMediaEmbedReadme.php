#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;

$sites = simplexml_load_file(__DIR__ . '/../src/s9e/TextFormatter/Plugins/MediaEmbed/Configurator/sites.xml');

$html = [];
$html[] = '<table>';
$html[] = '	<tr>';
$html[] = '		<th>Id</th>';
$html[] = '		<th>Site</th>';
$html[] = '		<th>Example URLs</th>';
$html[] = '	</tr>';

foreach ($sites->site as $site)
{
	$html[] = '	<tr>';
	$html[] = '		<td><code>' . $site['id'] . '</code></td>';
	$html[] = '		<td>' . $site->name . '</td>';
	$html[] = '		<td>' . implode('<br/>', (array) $site->example) . '</td>';
	$html[] = '	</tr>';
}

$html[] = '</table>';

$filepath = __DIR__ . '/../src/s9e/TextFormatter/Plugins/MediaEmbed/README.md';
$file     = file_get_contents($filepath);
$pos      = strpos($file, '<table>');

if ($pos === false)
{
	die("Could not find table\n");
}

$file = substr($file, 0, $pos) . str_replace('&', '&amp;', implode("\n", $html));

file_put_contents($filepath, $file);

die("Done.\n");