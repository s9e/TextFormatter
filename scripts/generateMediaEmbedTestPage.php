#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

$configurator = new s9e\TextFormatter\Configurator;
$configurator->BBCodes->addFromRepository('H2');

$sites = simplexml_load_file(__DIR__ . '/../src/s9e/TextFormatter/Plugins/MediaEmbed/Configurator/sites.xml');

$text = '';
foreach ($sites->site as $site)
{
	$configurator->MediaEmbed->add($site['id']);

	$text .= "[H2]" . $site->name . "[/H2]\n";

	foreach ($site->example as $example)
	{
		$text .= '[media]' . $example . "[/media]\n";
	}
}

$parser   = $configurator->getParser();
$renderer = $configurator->getRenderer();

file_put_contents('/tmp/MediaEmbed.html', $renderer->render($parser->parse($text)));

die("Done.\n");