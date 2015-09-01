#!/usr/bin/php
<?php

include __DIR__ . '/../src/autoloader.php';

$normalizer = new s9e\TextFormatter\Configurator\TemplateNormalizer;
$provider = new s9e\TextFormatter\Plugins\MediaEmbed\Configurator\LiveSiteDefinitionProvider(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/sites');
$cache = [];
foreach ($provider->getIds() as $siteId)
{
	$siteConfig = array_intersect_key(
		$provider->get($siteId),
		[
			'unresponsive' => 1,
			'attributes'   => 1,
			'host'         => 1,
			'scheme'       => 1,
			'extract'      => 1,
			'scrape'       => 1,
			'iframe'       => 1,
			'flash'        => 1,
		]
	);

	foreach (['flash', 'iframe'] as $type)
	{
		if (!isset($siteConfig[$type]))
		{
			continue;
		}
		foreach ($siteConfig[$type] as $attrName => $attrValue)
		{
			if (strpos($attrValue, '<xsl:') === false)
			{
				continue;
			}

			$siteConfig[$type][$attrName] = $normalizer->normalizeTemplate($attrValue);
		}
	}

	$cache[$siteId] = $siteConfig;
}

ksort($cache);
$php = '';
foreach ($cache as $siteId => $siteConfig)
{
	$php .= "\n\t\t" . var_export($siteId, true) . '=>' . var_export(serialize($siteConfig), true) . ',';
}
$php = rtrim($php, ',');

$filepath = realpath(__DIR__ . '/../src/Plugins/MediaEmbed/Configurator/CachedSiteDefinitionProvider.php');
$oldFile = file_get_contents($filepath);
$newFile = preg_replace_callback(
	'((?<=\\$cache = \\[).*?(?=\\n\\t\\];))s',
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