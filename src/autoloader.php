<?php

spl_autoload_register(
	function($className)
	{
		if (substr($className, 0, 18) === 's9e\\TextFormatter\\')
		{
			$path = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, substr($className, 17)) . '.php';
		}

		if (file_exists($path))
		{
			include $path;
		}
	}
);