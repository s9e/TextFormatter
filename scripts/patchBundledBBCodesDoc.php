#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$dom = new DOMDocument;
$dom->load(__DIR__ . '/../src/Plugins/BBCodes/Configurator/repository.xml');

$list = [];

$xpath = new DOMXPath($dom);
foreach ($dom->getElementsByTagName('bbcode') as $bbcode)
{
	$name     = $bbcode->getAttribute('name');
	$usage    = $bbcode->getElementsByTagName('usage')->item(0)->textContent;
	$template = $bbcode->getElementsByTagName('template')->item(0)->textContent;
	$template = trim(str_replace("\n\t\t\t", "\n", $template));

	$list[] = '###### ' . $name;
	$list[] = '```' . $usage . '```';
	$list[] = "```xsl\n" . $template . "\n```";

	$vars = [];
	foreach ($xpath->query('template/var | usage/var', $bbcode) as $var)
	{
		$name = $var->getAttribute('name');
		if (!isset($vars[$name]))
		{
			$vars[$name] = [
				'default'     => htmlspecialchars($var->textContent),
				'description' => $var->getAttribute('description')
			];
		}
	}
	if (!empty($vars))
	{
		$list[] = '<table>';
		$list[] = '	<tr>';
		$list[] = '		<th>Var name</th>';
		$list[] = '		<th>Default</th>';
		$list[] = '		<th>Description</th>';
		$list[] = '	</tr>';
		foreach ($vars as $name => $var)
		{
			$list[] = '	<tr>';
			$list[] = '		<td><code>' . $name . '</code></td>';
			$list[] = '		<td>' . $var['default'] . '</td>';
			$list[] = '		<td>' . $var['description'] . '</td>';
			$list[] = '	</tr>';
		}
		$list[] = '</table>';
	}

	$list[] = '';
}

$filepath = __DIR__ . '/../docs/Plugins/BBCodes/Add_from_the_repository.md';
file_put_contents(
	$filepath,
	preg_replace(
		'/(?<=### List of bundled BBCodes\\n\\n).*/s',
		implode("\n", $list),
		file_get_contents($filepath)
	)
);

echo "Patched $filepath.\n";