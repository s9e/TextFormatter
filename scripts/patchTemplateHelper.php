#!/usr/bin/php
<?php

use s9e\SimpleDOM\SimpleDOM;
use s9e\TextFormatter\Configurator\Helpers\RegexpBuilder;

include __DIR__ . '/../src/autoloader.php';

function loadPage($url, $filename = null)
{
	$filepath = sys_get_temp_dir() . '/' . basename($url);
	if (!isset($filename))
	{
		$filename = basename($url);
	}

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

$attributes = [];

$query = '/html/body/table/tr/td[@title = "Type"]/a';
$page  = loadPage('http://www.w3.org/TR/html4/index/attributes.html', 'html40attributes.html');
$xpath = new DOMXPath($page);
foreach ($xpath->query($query) as $a)
{
	if (strpos($a->textContent, 'URI') !== false)
	{
		$attributes['URL'][] = trim($a->parentNode->parentNode->firstChild->textContent);
	}
}

$page  = loadPage('http://w3c.github.io/html/fullindex.html');
$xpath = new DOMXPath ($page);
$query = '//h3[@id="attributes-table"]/following-sibling::table/tbody/tr';
foreach ($xpath->query($query) as $tr)
{
	foreach (['CSS', 'URL'] as $type)
	{
		if (strpos($tr->textContent, $type) !== false)
		{
			$th = $tr->getElementsByTagName('th')->item(0);
			foreach (preg_split('/[;\\s]+/', $th->textContent, -1, PREG_SPLIT_NO_EMPTY) as $attrName)
			{
				$attributes[$type][] = $attrName;
			}
		}
	}
}

// Prefill with known attributes from HTML 5.0 and HTML 4.01
$regexps = [
	'CSS' => [
		'^style$'
	],
	'JS'  => [
		'^on',
		'^data-s9e-livepreview-postprocess$'
	],
	'URL' => [
		'^action$',
		'^cite$',
		'^data$',
		'^formaction$',
		'^href$',
		'^icon$',
		'^manifest$',
		'^pluginspage$',
		'^poster$',
		// Covers "src" as well as non-standard attributes "dynsrc", "lowsrc"
		'src$'
	],
];

foreach ($attributes as $type => $attrNames)
{
	foreach ($attrNames as $attrName)
	{
		foreach ($regexps[$type] as $regexp)
		{
			// Test whether this attribute is already covered
			if (preg_match('/' . $regexp . '/i', $attrName))
			{
				continue 2;
			}
		}

		$regexps[$type][] = '^' . $attrName . '$';
	}
}

$filepath = __DIR__ . '/../src/Configurator/Helpers/TemplateHelper.php';
$file = file_get_contents($filepath);

foreach ($regexps as $type => $typeRegexps)
{
	$regexp = RegexpBuilder::fromList(
		$typeRegexps,
		[
			'delimiter'    => '/',
			'specialChars' => ['^' => '^', '$' => '$']
		]
	);

	$file = preg_replace_callback(
		'/(function get' . $type . 'Nodes\\(.*?\\$regexp = )\'.*?\'/s',
		function ($m) use ($regexp)
		{
			return $m[1] . var_export('/' . $regexp . '/i', true);
		},
		$file,
		1,
		$cnt
	);

	if ($cnt !== 1)
	{
		die("Could not find $type\n");
	}
}

file_put_contents($filepath, $file);

die("Done.\n");