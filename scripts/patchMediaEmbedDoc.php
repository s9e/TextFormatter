#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$html = [];
$html[] = '<table>';
$html[] = '	<tr>';
$html[] = '		<th>Id</th>';
$html[] = '		<th>Example URLs</th>';
$html[] = '	</tr>';

$params = [];

$configurator = new s9e\TextFormatter\Configurator;
foreach ($configurator->MediaEmbed->defaultSites as $siteId => $site)
{
	$html[] = '	<tr title="' . $site['name'] . '" id="' . $siteId . '">';
	$html[] = '		<td style="font-size:75%"><code>' . $siteId . '</code></td>';
	$html[] = '		<td style="font-size:50%">' . implode('<br/>', (array) $site['example']) . '</td>';
	$html[] = '	</tr>';

	foreach ($site['parameters'] ?? [] as $name => $info)
	{
		$params[$name] = $info['title'];
	}
}
$html[] = '</table>';
patchFile(__DIR__ . '/../docs/Plugins/MediaEmbed/Sites.md', $html);

ksort($params);
$html = ['<table>'];
foreach ($params as $name => $title)
{
	$html[] = '	<tr>';
	$html[] = '		<td>' . $name . '</td>';
	$html[] = '		<td>' . $title . '</td>';
	$html[] = '	</tr>';
}
$html[] = '</table>';
patchFile(__DIR__ . '/../docs/Plugins/MediaEmbed/Using_default_sites.md', $html);

function patchFile(string $filepath, array $html)
{
	$old      = file_get_contents($filepath);
	$pos      = strpos($old, '<table>');
	if ($pos === false)
	{
		die("Could not find table\n");
	}

	$new = substr($old, 0, $pos) . str_replace('&', '&amp;', implode("\n", $html));
	if ($new !== $old)
	{
		file_put_contents($filepath, $new);
		echo "Patched $filepath\n";
	}
}

die("Done.\n");