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

	$constants = array();

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

		// Collect and remove all of the tokens used for the constant's value
		$i = $start;
		$php = '';
		do
		{
			$php .= (is_array($tokens[$i])) ? $tokens[$i][1] : $tokens[$i];
			$tokens[$i] = '';
		}
		while (++$i < $end);

		// Save the last token's index and the constant's PHP representation
		$constants[$constName] = array($end, $php);
	}

	if ($constants)
	{
		$values  = array();
		$changed = true;

		foreach ($constants as $constName => $constant)
		{
			list($i, $php) = $constant;

			// Hardcode the constant resolution for the time being, since there are so few constants
			// that use a scalar expression
			$php = preg_replace_callback(
				'(self::(\w+))',
				function ($m) use ($values)
				{
					return var_export($values[$m[1]], true);
				},
				$php
			);

			if (!preg_match('(^(?:[ \\d<|]+|[\'"][^\'"]*[\'"]+)$)', $php))
			{
				die('Cannot evaluate ' . $php . ' as constant ' . $constName);
			}

			$value = eval('return ' . $php . ';');
			$values[$constName] = $value;
			$tokens[$i] = var_export($value, true) . ';';
		}
	}

	if ($changed)
	{
		$php = '';
		foreach ($tokens as $token)
		{
			$php .= (is_array($token)) ? $token[1] : $token;
		}

		echo "\r\x1B[KReplacing $filepath ";
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
		echo "\r\x1B[KReplacing $filepath ";
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

include_once __DIR__ . '/../../vendor/autoload.php';
convertDir(realpath(__DIR__ . '/../../src'));
echo "\n";
