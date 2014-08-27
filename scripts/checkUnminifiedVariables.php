#!/usr/bin/php
<?php

$usedVars = [];
foreach (glob(__DIR__ . '/../tests/.cache/minifier.*.js') as $filepath)
{
	preg_match_all('/\\w{2,}+(?=\\.\\w+[(=,])/S', file_get_contents($filepath), $m);
	$usedVars += array_flip($m[0]);
}

$knownVars = [];

// 2-letters minified names
foreach (range('a', 'z') as $c)
{
	$knownVars[$c . 'a'] = 1;
}

// Browser stuff
$knownVars['Math']      = 1;
$knownVars['Object']    = 1;
$knownVars['contentWindow'] = 1;
$knownVars['data']      = 1;
$knownVars['document']  = 1;
$knownVars['punycode']  = 1;
$knownVars['hljs']      = 1;
$knownVars['prototype'] = 1;
$knownVars['this']      = 1;
$knownVars['src']       = 1;
$knownVars['style']     = 1;
$knownVars['url']       = 1;
$knownVars['window']    = 1;

// Known false positives
$knownVars['pok'] = 1;

$unknownVars = array_diff_key($usedVars, $knownVars);
if ($unknownVars)
{
	echo 'Found unminified vars: ', implode(', ', array_keys($unknownVars)), "\n";
	exit(1);
}