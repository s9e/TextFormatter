#!/usr/bin/php
<?php

$dir = __DIR__ . '/../src/Parser';
$js  = file_get_contents($dir . '/Logger.js');

preg_match_all('((/\\*[^.]*?\\*/\\s*)Logger\\.prototype\\.(\\w+) = (function\\([^\\)]*\\)))', $js, $matches, PREG_SET_ORDER);

$js = '/**@constructor*/function Logger(){}Logger.prototype={';
foreach ($matches as list($line, $doc, $funcName, $signature))
{
	$js .= $funcName . ':' . $doc . $signature . '{},';
}
$js = substr($js, 0, -1) . '};';

file_put_contents($dir . '/NullLogger.js', $js);

die("Done.\n");