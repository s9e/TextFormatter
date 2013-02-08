<?php

spl_autoload_register(
	function($className)
	{
		if (preg_match('#^s9e\\\\TextFormatter(\\\\[\\w\\\\]+)$#D', $className, $m))
		{
			$path = __DIR__ . str_replace('\\', DIRECTORY_SEPARATOR, $m[1]) . '.php';

			if (file_exists($path))
			{
				include $path;
			}
		}
	}
);