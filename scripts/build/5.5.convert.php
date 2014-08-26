<?php

namespace s9e\TextFormatter\Build\PHP55;

$version = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : PHP_VERSION;

if (version_compare($version, '5.6', '>='))
{
	echo 'No need to run ', __FILE__, ' on PHP ', $version, "\n";
	return;
}

function convertScalarExpressionsInConstants($filepath, &$file)
{
	$tokens = token_get_all($file);

	$i       = 0;
	$cnt     = count($tokens);
	$changed = false;

	while (++$i < $cnt)
	{
		if ($tokens[$i][0] !== T_CONST)
		{
			continue;
		}

		while ($tokens[++$i][0] === T_WHITESPACE);
		$constName = $tokens[$i][1];

		// Skip to the next equal sign then skip its whitespace
		while ($tokens[++$i]    !== '=');
		while ($tokens[++$i][0] === T_WHITESPACE);

		// Save the position of the first token after the whitespace
		$start = $i;

		// Skip to the end of statement
		while ($tokens[++$i] !== ';');

		// Save the position of the semicolon
		$end = $i;

		if ($start === $end - 1)
		{
			// If there's only one token we skip this constant
			continue;
		}

		// Nuke all of the tokens used for the constant's value
		$i = $start;
		do
		{
			$tokens[$i] = '';
		}
		while (++$i < $end);

		$className = 's9e\\TextFormatter' . strtr(substr($filepath, strlen(realpath(__DIR__ . '/../../src')), -4), '/', '\\');

		// Replace the semicolon with the result of the operation
		$tokens[$end] = var_export(constant($className . '::' . $constName), true) . ';';

		$changed = true;
	}

	if ($changed)
	{
		$php = '';
		foreach ($tokens as $token)
		{
			$php .= (is_array($token)) ? $token[1] : $token;
		}

		echo "Replacing $filepath\n";
		file_put_contents($filepath, $php);
	}
}

function convertFile($filepath)
{
	$file    = file_get_contents($filepath);
	$oldFile = $file;

	convertScalarExpressionsInConstants($filepath, $file);

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

include_once __DIR__ . '/../../src/autoloader.php';
convertDir(realpath(__DIR__ . '/../../src'));
