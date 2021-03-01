#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

function getMethodAnnotations($className)
{
	static $methods = [];
	if (isset($methods[$className]))
	{
		return $methods[$className];
	}

	$class = new ReflectionClass($className);
	foreach ($class->getMethods(ReflectionMethod::IS_PUBLIC) as $k => $method)
	{
		$methodName = $method->getName();

		// It's easier to hardcode asConfig() than properly dig through ancestry
		if ($methodName === 'asConfig')
		{
			$methods[$className]['asConfig'] = [
				'description' => '',
				'methodSig'   => 'asConfig()',
				'returnType'  => 'array'
			];

			continue;
		}

		$doc = $method->getDocComment();
		if (strpos($doc, '{@inheritdoc}') !== false)
		{
			$parentMethods = getMethodAnnotations($class->getParentClass()->getName());
			$methods[$className][$methodName] = $parentMethods[$methodName];

			continue;
		}

		$returnType = (preg_match('(@return\\s*(\\S+))', $doc, $m)) ? $m[1] : 'void';

		preg_match_all('(@param\\s+(\\S+)\\s+(\\S+))', $doc, $matches, PREG_SET_ORDER);
		$args = [];
		foreach ($matches as $m)
		{
			$args[] = $m[1] . ' ' . $m[2];
		}

		$desc = '';
		if (preg_match('(^[\\s\\*/]++\\K(?!@).*)', $doc, $m))
		{
			$desc = $m[0];
		}

		$methods[$className][$methodName] = [
			'description' => $desc,
			'returnType'  => $returnType,
			'methodSig'   => $methodName . '(' . implode(', ', $args) . ')'
		];
	}

	ksort($methods[$className]);

	return $methods[$className];
}

function formatMethods(array $methods)
{
	$lenSig  = getColumnSize($methods, 'methodSig',  30);
	$lenType = getColumnSize($methods, 'returnType', 12);
	$return  = [];
	foreach ($methods as $methodName => $method)
	{
		$text = str_pad($method['returnType'], $lenType)
		      . ' '
		      . str_pad($method['methodSig'], $lenSig)
		      . ' '
		      . $method['description'];

		$return[$methodName] = trim($text);
	}

	return $return;
}

function getColumnSize(array $rows, string $columnName, int $max)
{
	$size = 0;
	foreach ($rows as $row)
	{
		$len = strlen($row[$columnName]);
		if ($len > $size && $len <= $max)
		{
			$size = $len;
		}
	}

	return $size;
}

function patchDir($dirpath)
{
	$dirpath = realpath($dirpath);
	array_map('patchDir',  glob($dirpath . '/*', GLOB_ONLYDIR));
	array_map('patchFile', glob($dirpath . '/*.php'));
}

function patchFile($filepath)
{
	$file = file_get_contents($filepath);

	if (strpos($file, 'use CollectionProxy') === false)
	{
		return;
	}

	if (!preg_match('#@var (\\S+)[^\\n]*\\s+\\*/\\s+\\S+ \\$collection;#', $file, $m))
	{
		echo "Cannot find collection in $filepath\n";
		return;
	}
	$className = $m[1];

	if ($className[0] === '\\')
	{
		$className = substr($className, 1);
	}
	else
	{
		if (preg_match('#use (\\S*\\\\' . $className . ');#', $file, $m))
		{
			$className = $m[1];
		}
		else
		{
			preg_match('#namespace (s9e[^;]+)#', $file, $m);
			$className = $m[1] . '\\' . $className;
		}
	}

	$methods = formatMethods(getMethodAnnotations($className));

	$old  = $file;
	$file = preg_replace_callback(
		"#(?<=\n)(?:(/[*]+\n(?:[*](?! @method).*\n)*?)(?:[*]\n)*(?:[*] @method.*\n)*[*]/\n)?class#",
		function ($m) use ($methods)
		{
			$doc = (isset($m[1])) ? $m[1] : "\n/**\n";

			foreach ($methods as $text)
			{
				$doc .= '* @method ' . $text . "\n";
			}

			$doc .= "*/\nclass";

			return $doc;
		},
		$file
	);

	if ($file !== $old)
	{
		echo "Patched $filepath\n";

		file_put_contents($filepath, $file);
	}
}

patchDir(__DIR__ . '/../src');
//getMethodAnnotations('s9e\\TextFormatter\\Configurator\\Collections\\RulesGeneratorList');

die("Done.\n");