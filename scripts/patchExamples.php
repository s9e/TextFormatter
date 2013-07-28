#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

// Reuse the caching hack from the MediaEmbed tests
eval('namespace s9e\TextFormatter\Tests;class Test{}');
include __DIR__ . '/../tests/bootstrap.php';
include __DIR__ . '/../tests/Plugins/MediaEmbed/ParserTest.php';

function patchDir($dirpath)
{
	$dirpath = realpath($dirpath);
	array_map('patchDir',  glob($dirpath . '/*', GLOB_ONLYDIR));
	array_map('patchFile', glob($dirpath . '/*.md'));
}

function patchFile($filepath)
{
	$file = file_get_contents($filepath);

	// Execute the lone PHP in BasicUsage.md
	if (strpos($filepath, 'BasicUsage.md'))
	{
		$text = preg_replace_callback(
			'#```php([^`]+)\\n```\\s+(?!```html|<pre>)#s',
			function ($m)
			{
				eval($m[1]);

				return $m[0];
			},
			$file
		);
	}

	// Execute PHP and replace output
	$text = preg_replace_callback(
		'#(```php([^`]+)\\n```\\s+(?:```\\w+|<pre>)).*?(\\n(?:```|</pre>)(?:\\n|$))#s',
		function ($m)
		{
			ob_start();
			eval($m[2]);

			return $m[1] . "\n" . ob_get_clean() . $m[3];
		},
		$file
	);

	if ($text === $file)
	{
		echo "Skipping $filepath\n";
	}
	else
	{
		echo "Patching $filepath\n";
		file_put_contents($filepath, $text);
	}
}

patchDir(__DIR__ . '/../src/s9e/TextFormatter/Plugins/');
patchDir(__DIR__ . '/../docs/Cookbook/');

die("Done.\n");