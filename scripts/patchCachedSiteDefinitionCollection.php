#!/usr/bin/php
<?php

use s9e\TextFormatter\Configurator\TemplateNormalizer;
use s9e\TextFormatter\Plugins\MediaEmbed\Configurator\Collections\XmlFileDefinitionCollection;

include __DIR__ . '/../vendor/autoload.php';

function export(array $arr)
{
	$exportKeys = (array_keys($arr) !== range(0, count($arr) - 1));
	ksort($arr);

	$entries = [];
	foreach ($arr as $k => $v)
	{
		$entries[] = (($exportKeys) ? var_export($k, true) . '=>' : '')
				   . ((is_array($v)) ? export($v) : var_export($v, true));
	}

	return '[' . implode(',', $entries) . ']';
}

$path       = __DIR__ . '/../src/Plugins/MediaEmbed/Configurator/sites';
$cache      = iterator_to_array(new XmlFileDefinitionCollection($path));
$normalizer = new s9e\TextFormatter\Configurator\TemplateNormalizer;
foreach ($cache as $siteId => $siteConfig)
{
	$siteConfig = array_intersect_key(
		$siteConfig,
		[
			'attributes'   => 1,
			'choose'       => 1,
			'extract'      => 1,
			'flash'        => 1,
			'host'         => 1,
			'iframe'       => 1,
			'name'         => 1,
			'parameters'   => 1,
			'scheme'       => 1,
			'scrape'       => 1,
			'tags'         => 1
		]
	);
	if (isset($siteConfig['tags']))
	{
		$siteConfig['tags'] = (array) $siteConfig['tags'];
		$siteConfig['tags'] = (array) end($siteConfig['tags']);
	}
	foreach (['flash', 'iframe'] as $type)
	{
		if (!isset($siteConfig[$type]))
		{
			continue;
		}
		array_walk_recursive(
			$siteConfig[$type],
			function (&$attrValue) use ($normalizer)
			{
				if (strpos($attrValue, '<xsl:') !== false)
				{
					$attrValue = $normalizer->normalizeTemplate($attrValue);
				}
			}
		);
	}

	$cache[$siteId] = $siteConfig;
}

ksort($cache);
$php = '';
foreach ($cache as $siteId => $siteConfig)
{
	$php .= "\n\t\t" . var_export($siteId, true) . '=>' . export($siteConfig) . ',';
}
$php = rtrim($php, ',');

$filepath = realpath(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/Collections/CachedDefinitionCollection.php');
$oldFile = file_get_contents($filepath);
$newFile = preg_replace_callback(
	'((?<=\\$items = \\[).*?(?=\\n\\t\\];))s',
	function () use ($php)
	{
		return $php;
	},
	$oldFile
);

if ($newFile !== $oldFile)
{
	file_put_contents($filepath, $newFile);
	echo "Replaced $filepath\n";
}

die("Done.\n");