#!/usr/bin/php
<?php

function patchFile($filepath)
{
	$file = file_get_contents($filepath);

	// Execute PHP and replace output
	$text = preg_replace_callback(
		'#(```md\\n(.*?)\\n```\\s+```html).*?(\\n```)#s',
		function ($m)
		{
			global $parser, $renderer;

			$xml  = $parser->parse($m[2]);
			$html = $renderer->render($xml);

			return $m[1] . "\n" . $html . $m[3];
		},
		$file
	);

	if ($text !== $file)
	{
		echo "\x1b[1mPatching $filepath\x1b[0m\n";
		file_put_contents($filepath, $text);
	}
}

include __DIR__ . '/../src/autoloader.php';
$configurator = new s9e\TextFormatter\Configurator;
$configurator->Litedown;
extract($configurator->finalize());

patchFile(__DIR__ . '/../src/Plugins/Litedown/Syntax.md');
patchFile(__DIR__ . '/../docs/Plugins/Litedown/Syntax.md');

die("Done.\n");