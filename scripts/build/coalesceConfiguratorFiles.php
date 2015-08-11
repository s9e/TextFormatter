#!/usr/bin/php
<?php

include __DIR__ . '/../../src/autoloader.php';
$configurator = new s9e\TextFormatter\Configurator;
$tag = $configurator->tags->add('X');
$tag->attributes->add('x')->filterChain->append('#url');
$tag->template = '<a href="{@url}"><xsl:apply-templates/></a>';
$configurator->rendering->engine = 'PHP';
$configurator->finalize();

$scores = $relations = array();
foreach (get_declared_classes() as $className)
{
	if (strpos($className, 's9e\\TextFormatter\\Configurator\\') !== 0)
	{
		continue;
	}
	$scores[$className] = 0;
	$relations[$className] = array();
	$class = new ReflectionClass($className);
	foreach ($class->getInterfaceNames() as $interfaceName)
	{
		if (strpos($interfaceName, 's9e\\TextFormatter\\Configurator\\') !== 0)
		{
			continue;
		}
		$scores[$interfaceName] = 0;
		$relations[$className][] = $interfaceName;
	}
	if (method_exists($class, 'getTraitNames'))
	{
		foreach ($class->getTraitNames() as $traitName)
		{
			if (strpos($traitName, 's9e\\TextFormatter\\Configurator\\') !== 0)
			{
				continue;
			}
			$scores[$traitName] = 0;
			$relations[$className][] = $traitName;
		}
	}
	$parentClass = $class->getParentClass();
	if ($parentClass)
	{
		$parentName = $parentClass->getName();
		if (strpos($parentName, 's9e\\TextFormatter\\Configurator\\') === 0)
		{
			$relations[$className][] = $parentName;
		}
	}
}

do
{
	$continue = false;
	foreach ($relations as $className => $relationNames)
	{
		foreach ($relationNames as $relationName)
		{
			if ($scores[$className] <= $scores[$relationName])
			{
				$scores[$className] = 1 + $scores[$relationName];
				$continue = true;
			}
		}
	}
}
while ($continue);

$classNamesByScore = array();
foreach ($scores as $className => $score)
{
	$classNamesByScore[$score][] = $className;
}
ksort($classNamesByScore);

$rootDir = realpath(__DIR__ . '/../../src');
$target = $rootDir . '/Configurator.php';
$file = file_get_contents($target);
foreach ($classNamesByScore as $classNames)
{
	sort($classNames);
	foreach ($classNames as $className)
	{
		$filepath = $rootDir . strtr(substr($className, 17), '\\', '/') . '.php';
		$file .= substr(file_get_contents($filepath), 5);
		unlink($filepath);
	}
}

// Fix __DIR__ in FunctionProvider
$file = str_replace(
	"\$filepath = __DIR__ . '/functions/' . \$funcName . '.js';",
	"\$filepath = __DIR__ . '/Configurator/JavaScript/functions/' . \$funcName . '.js';",
	$file
);

file_put_contents($target, $file);