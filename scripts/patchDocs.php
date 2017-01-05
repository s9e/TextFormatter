#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

function patchDir($dirpath)
{
	$dirpath = realpath($dirpath);
	array_map('patchDir',  glob($dirpath . '/*', GLOB_ONLYDIR));
	array_map('patchFile', glob($dirpath . '/*.md'));
}

function patchFile($filepath)
{
	$file = file_get_contents($filepath);

	// Execute PHP and replace output
	$text = preg_replace_callback(
		'#
			(?<block>```php(?<code>[^`]++)```)
			\\s*
			(?<open>```\\w*|<pre>(?:<code>)?)
			(?<output>[^`]*)
			(?<close>```|</code></pre>)(?=\\n|$)
		#sx',
		function ($m)
		{
			$php = preg_replace(
				'/\\$configurator =.*/',
				"\$0\n\$configurator->registeredVars['cacheDir'] = " . var_export(__DIR__ . '/../tests/.cache', true) . ";\n",
				$m['code']
			);

			ob_start();
			eval($php);
			$output = rtrim(ob_get_clean(), "\n");

			return $m['block'] . "\n" . $m['open'] . "\n" . $output . "\n" . $m['close'];
		},
		$file
	);

	if ($text !== $file)
	{
		echo "\x1b[1mPatching $filepath\x1b[0m\n";
		file_put_contents($filepath, $text);
	}
}

patchDir(__DIR__ . '/../docs/Bundles');
patchDir(__DIR__ . '/../docs/Filters');
patchDir(__DIR__ . '/../docs/Getting_started');
patchDir(__DIR__ . '/../docs/Plugins');
patchDir(__DIR__ . '/../docs/Rules');
patchDir(__DIR__ . '/../docs/Templating');

die("Done.\n");