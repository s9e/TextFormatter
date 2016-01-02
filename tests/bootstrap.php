<?php

include __DIR__ . '/../src/autoloader.php';

if (file_exists(__DIR__ . '/../vendor/autoload.php'))
{
	include __DIR__ . '/../vendor/autoload.php';
}

spl_autoload_register(
	function($className)
	{
		if (preg_match('#^s9e\\\\TextFormatter\\\\Tests(\\\\[\\w\\\\]+)$#D', $className, $m))
		{
			$path = __DIR__ . str_replace('\\', '/', $m[1]) . '.php';

			if (file_exists($path))
			{
				include $path;
			}
		}
	}
);

date_default_timezone_set('UTC');