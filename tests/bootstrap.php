<?php

include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

spl_autoload_register(
	function($className)
	{
		if (substr($className, 0, 24) === 's9e\\TextFormatter\\Tests\\')
		{
			$path = __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 23)) . '.php';

			if (file_exists($path))
			{
				include $path;
			}
		}
	}
);