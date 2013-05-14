#!/usr/bin/php
<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

foreach (glob(__DIR__ . '/../src/s9e/TextFormatter/Plugins/*/README.md') as $filepath)
{
	$text = preg_replace_callback(
		'/(```php(.*?)```.*?```html).*?```/s',
		function ($m)
		{
			ob_start();
			eval($m[2]);

			return $m[1] . "\n" . ob_get_clean() . "\n```";
		},
		file_get_contents($filepath)
	);

	file_put_contents($filepath, $text);
}

die("Done.\n");