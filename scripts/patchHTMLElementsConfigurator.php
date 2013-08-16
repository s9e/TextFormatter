#!/usr/bin/php
<?php

use s9e\SimpleDOM\SimpleDOM;

include 's9e/SimpleDOM/src/SimpleDOM.php';

$filepath = '/tmp/index.html';

if (!file_exists($filepath))
{
	copy(
		'compress.zlib://http://www.w3.org/html/wg/drafts/html/master/index.html',
		$filepath,
		stream_context_create(['http' => ['header' => 'Accept-Encoding: gzip']])
	);
}

$page  = SimpleDOM::loadHTMLFile($filepath);
$table = [];

$query = '/html/body/h3[@id="attributes-1"]/following-sibling::table[1]/tbody/tr[contains(td[3],"URL")]';
foreach ($page->xpath($query) as $tr)
{
	foreach (preg_split('/[;\\s]+/', $tr->th->textContent(), -1, PREG_SPLIT_NO_EMPTY) as $attrName)
	{
		// We manually ignore itemprop because it accepts plain text as well as URLs.
		// We also ignore itemid because it actually accepts any URIs, not just URLs
		if ($attrName === 'itemprop' || $attrName === 'itemid')
		{
			continue;
		}

		$table[$attrName] = '#url';
	}
}

ksort($table);

$len = max(array_map('strlen', array_keys($table)));
$php = '';
foreach ($table as $attrName => $filterName)
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