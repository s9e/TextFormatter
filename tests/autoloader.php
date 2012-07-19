<?php

spl_autoload_register(
	function($className)
	{
		if (substr($className, 0, 18) === 's9e\\TextFormatter\\')
		{
			if (substr($className, 18, 6) === 'Tests\\')
			{
				$path = __DIR__ . '/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 23)) . '.php';
			}
			else
			{
				$path = __DIR__ . '/../src/' . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 17)) . '.php';
			}

			if (file_exists($path))
			{
				include $path;
			}
		}
	}
);