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

$page = loadPage('http://www.w3.org/html/wg/drafts/html/master/text-level-semantics.html');

$query = '/html/body/h3[@id="attributes-1"]/following-sibling::table[1]/tbody/tr[contains(td[3],"URL")]';

$table = $page->getElementById('attributes-1')->nextSibling;
while ($table->nodeName !== 'table')
{
	$table = $table->nextSibling;
}

$filters = [];
foreach ($table->getElementsByTagName('tr') as $tr)
{
	if (strpos($tr->textContent, 'URL') === false)
	{
		continue;
	}

	$th = $tr->getElementsByTagName('th')->item(0);

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

$filepath = __DIR__ . '/../src/s9e/TextFormatter/Plugins/HTMLElements/Configurator.php';
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