<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

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