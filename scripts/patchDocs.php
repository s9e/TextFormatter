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

			// Replace generated IDs with a placeholder
			$output = preg_replace('(task-id="\\K\\w++)', '...', $output);

			return $m['block'] . "\n" . $m['open'] . "\n" . $output . "\n" . $m['close'];
		},
		$file
	);

	if ($text !== $file)
	{
		echo "\x1b[1mPatching $filepath\x1b[0m\n";

exit;
		file_put_contents($filepath, $text);
	}
}

patchDir(__DIR__ . '/../docs');
@unlink('/tmp/MyBundle.php');
@unlink('/tmp/MyRenderer.php');

die("Done.\n");