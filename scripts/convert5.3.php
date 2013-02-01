#!/usr/bin/php
<?php

if (version_compare(PHP_VERSION, '5.4.0') >= 0)
{
	die('No need to convert sources on PHP ' . PHP_VERSION . "\n");
}

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

function convertCustom($filepath, &$file)
{
	// Some specific tweaks for PHP 5.3 that would be considered bad code in 5.4
	$replacements = array(
		'BBCodeMonkey.php' => array(
			array(
				'$xpath = new DOMXPath($dom);',
				'$xpath = new DOMXPath($dom);$_this=$this;'
			),
			array(
				'function ($m) use ($tokens, $passthroughToken)',
				'function ($m) use ($_this, $tokens, $passthroughToken)'
			),
			array(
				'if ($this->isFilter($tokenId))',
				'if ($_this->isFilter($tokenId))'
			),
			array(
				'protected function isFilter($tokenId)',
				'public function isFilter($tokenId)'
			)
		),
		'FilterProcessingTest.php' => array(
			array(
				"\n\t\t\$filter = new ProgrammableCallback(\n\t\t\tfunction()",
				"\n\t\t\$test = \$this;\n\t\t\$filter = new ProgrammableCallback(\n\t\t\tfunction() use (\$test)"
			),
			array(
				"\n\t\t\t\t\$this->assert",
				"\n\t\t\t\t\$test->assert"
			),
			array(
				"\n\t\t\t\t\$this->fail",
				"\n\t\t\t\t\$test->fail"
			)
		),
		'Logger.php' => array(
			array(
				'$callback($msg, $context);',
				'call_user_func_array($callback, array(&$msg, &$context));'
			)
		),
		'PHP.php' => array(
			array(
				"protected \$defaultParams=[' . implode(',', \$params) . '];",
				"protected \$defaultParams=array(' . implode(',', \$params) . ');"
			),
			array(
				'protected $params=[];',
				'protected $params=array();'
			),
			array(
				'protected $userParams=[];',
				'protected $userParams=array();'
			),
			array(
				'$toks = [];',
				'$toks = array();'
			)
		),
		'Regexp.php' => array(
			array(
				"\$variant->setDynamic(\n\t\t\t'JS',\n\t\t\tfunction ()\n\t\t\t{\n\t\t\t\treturn \$this",
				"\$_this=\$this;\$variant->setDynamic(\n\t\t\t'JS',\n\t\t\tfunction () use (\$_this)\n\t\t\t{\n\t\t\t\treturn \$_this"
			)
		),
		'TemplateOptimizer.php' => array(
			array(
				'return $m[1] . self::minifyXPath($m[2]);',
				'return $m[1] . TemplateOptimizer::minifyXPath($m[2]);'
			)
		),
		'Variant.php' => array(
			array(
				'return ($isDynamic) ? $value() : $value;',
				'return ($isDynamic) ? call_user_func($value) : $value;'
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

function convertFile($filepath)
{
	$file    = file_get_contents($filepath);
	$oldFile = $file;

	convertUse($filepath, $file);
	convertCustom($filepath, $file);
	convertArraySyntax($file);

	if ($file !== $oldFile)
	{
		echo "Replacing $filepath\n";
		file_put_contents($filepath, $file);
	}
}

function convertUse($filepath, &$file)
{
	if (!strpos($file, "\tuse "))
	{
		return;
	}

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
			$path = __DIR__ . '/../src/s9e/TextFormatter' . str_replace('\\', DIRECTORY_SEPARATOR, substr($fqn, 17)) . '.php';

			$path = str_replace(
				'/../src/s9e/TextFormatter/Tests/',
				'/../tests/',
				$path
			);

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

function toShort($filepath)
{
	$file   = file_get_contents($filepath);
	$tokens = token_get_all($file);

	$i       = 0;
	$cnt     = count($tokens);
	$level   = 0;
	$replace = array();

	while (++$i < $cnt)
	{
		$token = $tokens[$i];

		if ($token === '(')
		{
			++$level;
		}
		elseif ($token === ')')
		{
			if ($level === end($replace))
			{
				$tokens[$i] = ']';
				array_pop($replace);
			}
			else
			{
				--$level;
			}
		}
		elseif (is_array($token) && $token[0] === T_ARRAY)
		{
			$j = $i;
			while ($tokens[++$j][0] === T_WHITESPACE);

			if ($tokens[$j] === '(')
			{
				do
				{
					unset($tokens[$i]);
				}
				while (++$i < $j);

				$tokens[$i] = '[';
				$replace[]  = $level;
			}
		}
	}

	$file = '';
	foreach ($tokens as $token)
	{
		$file .= (is_string($token)) ? $token : $token[1];
	}

	file_put_contents($filepath, $file);
}

function convertArraySyntax(&$file)
{
	$tokens = token_get_all($file);

	$i       = 0;
	$cnt     = count($tokens);
	$level   = 0;
	$replace = array();

	while (++$i < $cnt)
	{
		$token = $tokens[$i];

		if ($token === '[')
		{
			$j = $i;
			while ($tokens[--$j] === T_WHITESPACE);

			if ($tokens[$j] === ']')
			{
				++$level;
			}
			elseif (is_array($tokens[$j])
			    && ($tokens[$j][0] === T_STRING || $tokens[$j][0] === T_VARIABLE))
			{
				++$level;
			}
			else
			{
				$tokens[$i] = 'array(';
				$replace[]  = $level;
			}
		}
		elseif ($token === ']')
		{
			if ($level === end($replace))
			{
				$tokens[$i] = ')';
				array_pop($replace);
			}
			else
			{
				--$level;
			}
		}
	}

	$file = '';
	foreach ($tokens as $token)
	{
		$file .= (is_string($token)) ? $token : $token[1];
	}
}

convertDir(realpath(__DIR__ . '/../src/s9e/TextFormatter'));
convertDir(realpath(__DIR__ . '/../tests'));
