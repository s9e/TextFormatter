<?php

namespace s9e\TextFormatter\Build\PHP54;

$version = (isset($_SERVER['argv'][1])) ? $_SERVER['argv'][1] : PHP_VERSION;

if (version_compare($version, '5.5', '>='))
{
	echo 'No need to run ', __FILE__, ' on PHP ', $version, "\n";
	return;
}

function convertCustom($filepath, &$file)
{
	$replacements = array(
		'ClosureCompilerService.php' => array(
			array(
				"throw new RuntimeException('Closure Compiler service returned invalid JSON: ' . json_last_error_msg());",
				"\$msgs = array(
					JSON_ERROR_NONE => 'No error',
					JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
					JSON_ERROR_STATE_MISMATCH => 'State mismatch (invalid or malformed JSON)',
					JSON_ERROR_CTRL_CHAR => 'Control character error, possibly incorrectly encoded',
					JSON_ERROR_SYNTAX => 'Syntax error',
					JSON_ERROR_UTF8 => 'Malformed UTF-8 characters, possibly incorrectly encoded'
				);
				throw new RuntimeException('Closure Compiler service returned invalid JSON: ' . (isset(\$msgs[json_last_error()]) ? \$msgs[json_last_error()] : 'Unknown error'));"
			)
		),
		'TemplateHelper.php' => array(
			array(
				'$replacement = $fn(array_column($m, 0), $node);',
				'$_m=[];foreach($m as $v){$_m[]=$v[0];}$replacement = $fn($_m, $node);'
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
}

function convertForeachList($filepath, &$file)
{
	$file = preg_replace_callback(
		'#(\\s+as\\s+(?:\\$\\w+\\s*=>\\s*)?)(list\\([^)]+\\))(\\)\\s*\\{(\\s*))#',
		function ($m)
		{
			// Generate a var name based on replaced code
			$varName = '$_' . dechex(crc32($m[0]));

			return $m[1] . $varName . $m[3] . $m[2] . ' = ' . $varName . ';' . $m[4];
		},
		$file
	);
}

function convertFile($filepath)
{
	$file    = file_get_contents($filepath);
	$oldFile = $file;

	convertCustom($filepath, $file);
	convertForeachList($filepath, $file);

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

convertDir(realpath(__DIR__ . '/../../src'));
convertDir(realpath(__DIR__ . '/../../tests'));
echo "\n";
