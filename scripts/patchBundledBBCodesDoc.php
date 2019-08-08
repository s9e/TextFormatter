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

	$vars = $xpath->query('template/var | usage/var', $bbcode);
	if ($vars->length)
	{
		$list[] = '<table>';
		$list[] = '	<tr>';
		$list[] = '		<th>Var name</th>';
		$list[] = '		<th>Default</th>';
		$list[] = '		<th>Description</th>';
		$list[] = '	</tr>';
		foreach ($vars as $var)
		{
			$list[] = '	<tr>';
			$list[] = '		<td><code>' . $var->getAttribute('name') . '</code></td>';
			$list[] = '		<td>' . htmlspecialchars($var->textContent) . '</td>';
			$list[] = '		<td>' . $var->getAttribute('description') . '</td>';
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