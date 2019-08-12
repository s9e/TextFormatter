#!/usr/bin/php
<?php

$regexps = [
	// varName.foo() or varName= or varName,
	'/\\w{2,}+(?=\\.\\w+[(=,])/S',
	// varName= preceded by any of ; ( ) { }
	'/(?<=[;(){}])(?<!&amp;)\\w{2,}+(?==)/S'
];

$usedVars = [];
foreach (glob(__DIR__ . '/../tests/.cache/minifier.*.js') as $filepath)
{
	$file = file_get_contents($filepath);
	$file = preg_replace('(<script>.*?(?:<|\\\\x3c)/script>)s', '', $file);

	foreach ($regexps as $regexp)
	{
		preg_match_all($regexp, $file, $m);
		$usedVars += array_flip($m[0]);
	}
}

$knownVars = [];

// 2-letters minified names
foreach (range('a', 'z') as $c)
{
	$knownVars[$c . 'a'] = 1;
	$knownVars[$c . 'b'] = 1;
	$knownVars[$c . 'c'] = 1;
	$knownVars[$c . 'd'] = 1;
}
foreach (range('A', 'Z') as $c)
{
	$knownVars[$c . 'a'] = 1;
	$knownVars[$c . 'b'] = 1;
	$knownVars[$c . 'c'] = 1;
}

// Browser stuff
$knownVars['Math']          = 1;
$knownVars['Object']        = 1;
$knownVars['contentWindow'] = 1;
$knownVars['data']          = 1;
$knownVars['document']      = 1;
$knownVars['prototype']     = 1;
$knownVars['src']           = 1;
$knownVars['style']         = 1;
$knownVars['this']          = 1;
$knownVars['url']           = 1;
$knownVars['window']        = 1;

// Known false positives
$knownVars['hljs']        = 1;
$knownVars['hljsLoading'] = 1;
$knownVars['id']          = 1;
$knownVars['pok']         = 1;
$knownVars['punycode']    = 1;
$knownVars['s9e']         = 1;
$knownVars['site']        = 1;
$knownVars['port1']       = 1;
$knownVars['port2']       = 1;

// Those are intentionally preserved
$knownVars['host']   = 1;
$knownVars['scheme'] = 1;

$unknownVars = array_diff_key($usedVars, $knownVars);
if ($unknownVars)
{
	echo 'Found unminified vars: ', implode(', ', array_keys($unknownVars)), "\n";
	exit(1);
}