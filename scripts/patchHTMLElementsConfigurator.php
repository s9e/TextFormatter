#!/usr/bin/php
<?php

function loadPage($url)
{
	$filepath = sys_get_temp_dir() . '/' . basename($url);

	if (!file_exists($filepath))
	{
		copy(
			'compress.zlib://' . $url,
			$filepath,
			stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']])
		);
	}

	$page = new DOMDocument;
	$page->preserveWhiteSpace = false;
	@$page->loadHTMLFile($filepath, LIBXML_COMPACT | LIBXML_NOBLANKS);

	return $page;
}

$page  = loadPage('http://w3c.github.io/html/fullindex.html');
$xpath = new DOMXPath ($page);
$query = '//h3[@id="attributes-table"]/following-sibling::table/tbody/tr[contains(., "URL")]/th';

$filters = [];
foreach ($xpath->query($query) as $th)
{
	foreach (preg_split('/[;\\s]+/', $th->textContent, -1, PREG_SPLIT_NO_EMPTY) as $attrName)
	{
		// We manually ignore itemprop because it accepts plain text as well as URLs.
		// We also ignore itemid because it actually accepts any URIs, not just URLs
		if ($attrName === 'itemprop' || $attrName === 'itemid')
		{
			continue;
		}

		$filters[$attrName] = '#url';
	}
}

ksort($filters);

$len = max(array_map('strlen', array_keys($filters)));
$php = '';
foreach ($filters as $attrName => $filterName)
{
	$php .= "\n\t\t'$attrName'" . str_repeat(' ', $len - strlen($attrName)) . " => '$filterName',";
}

$php = substr($php, 0, -1) . "\n\t";

$filepath = __DIR__ . '/../src/Plugins/HTMLElements/Configurator.php';
$file = file_get_contents($filepath);
$file = preg_replace(
	'/(protected \\$attributeFilters = \\[)[^\\]]+/',
	'$1' . $php,
	$file,
	-1,
	$cnt
);

if ($cnt !== 1)
{
	die("Bad replacement count ($cnt) in $filepath\n");
}

file_put_contents($filepath, $file);

die("Done.\n");