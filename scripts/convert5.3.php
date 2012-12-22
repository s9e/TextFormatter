#!/usr/bin/php
<?php

function fqn($file)
{
	$table = array();
	if (!preg_match('#namespace ([^;]+)#', $file, $m))
	{
		die("Could not capture namespace from $filepath\n");
	}
	$namespace = $m[1];

	preg_match_all('#^use ([^;]+)#m', $file, $m);
	foreach ($m[1] as $fqn)
	{
		$table[preg_replace('#.*\\\\#', '', $fqn)] = $fqn;
	}

	return array($namespace, $table);
}

function convertFile($filepath)
{
	$file = file_get_contents($filepath);

	if (!strpos($file, "\tuse "))
	{
		return;
	}

	$oldFile = $file;
	list($namespace, $table) = fqn($file);

	// Hardcode a couple of names
	if (strpos($filepath, 'Parser.php'))
	{
		$table['BuiltInFilters'] = 's9e\\TextFormatter\\Parser\\BuiltInFilters';
		$table['Tag'] = 's9e\\TextFormatter\\Parser\\Tag';
	}

	$file = preg_replace_callback(
		'#^\\tuse ([^;]+);#m',
		function ($m) use ($namespace, &$table)
		{
			$fqn  = (isset($table[$m[1]])) ? $table[$m[1]] : $namespace . '\\' . $m[1];
			$path = __DIR__ . '/../src' . str_replace('\\', DIRECTORY_SEPARATOR, substr($fqn, 17)) . '.php';

			$path = str_replace('/../src/Tests/', '/../tests/', $path);

			if (!file_exists($path))
			{
				die("Cannot find $fqn in $path\n");
			}

			$file = file_get_contents($path);

			list(, $traitTable) = fqn($file);
			$table += $traitTable;

			preg_match('#\\n{\\n(.*)\\n}$#s', $file, $m);

			return $m[1];
		},
		$file
	);

	if ($table)
	{
		$table = array_unique($table);
		sort($table);

		$file = preg_replace('#^use.*?;\\n\\n#ms', 'use ' . implode(";\nuse ", $table) . ";\n\n", $file);
	}

	// Some specific tweaks for PHP 5.3 that would be considered bad code in 5.4
	$replacements = array(
		'FilterProcessingTest.php' => array(
			array(
				"\n\t\t\$filter = new ProgrammableCallback(",
				"\n\t\t\$test = \$this;\n\t\t\$filter = new ProgrammableCallback("
			),
			array(
				"\n\t\t\t\t\$this->assert",
				"\n\t\t\t\t\$test->assert"
			)
		),
		'Logger.php' => array(
			array(
				'$callback($msg, $context);',
				'call_user_func_array($callback, array(&$msg, &$context));'
			)
		),
		'TemplateOptimizer.php' => array(
			array(
				'return $m[1] . self::minifyXPath($m[2]);',
				'return $m[1] . TemplateOptimizer::minifyXPath($m[2]);'
			),
			array(
				'protected static function minifyXPath($old)',
				'public static function minifyXPath($old)'
			)
		),
	);

	$filename = basename($filepath);
	if (isset($replacements[$filename]))
	{
		foreach ($replacements[$filename] as $pair)
		{
			list($search, $replace) = $pair;
			$file = str_replace($search, $replace, $file);
		}
	}

	if ($file !== $oldFile)
	{
		echo "Replacing $filepath\n";
		file_put_contents($filepath, $file);
	}
}

function convertDir($dir)
{
	foreach (glob($dir . '/*', GLOB_ONLYDIR) as $sub)
	{
		convertDir($sub);
	}

	foreach (glob($dir . '/*.php') as $filepath)
	{
		convertFile($filepath);
	}
}

convertDir(realpath(__DIR__ . '/../src'));
convertDir(realpath(__DIR__ . '/../tests'));
