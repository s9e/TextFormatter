#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->FancyPants;
extract($configurator->finalize());

$filepath = realpath(__DIR__ . '/../docs/Plugins/FancyPants/Synopsis.md');
file_put_contents(
	$filepath,
	preg_replace_callback(
		'((<tr>.*?<td>.*?</td>.*?<td>(.*?)</td>.*?<td>).*?(</td>))s',
		function ($m) use ($parser, $renderer)
		{
			return $m[1] . $renderer->render($parser->parse(htmlspecialchars_decode($m[2]))) . $m[3];
		},
		file_get_contents($filepath)
	)
);

die("Done.\n");