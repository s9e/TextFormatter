#!/usr/bin/php
<?php

$dir = __DIR__ . '/../src/Parser';
$js  = file_get_contents($dir . '/Logger.js');

preg_match_all('/Logger\\.prototype\\.(\\w+) = function/', $js, $m);

$js = 'function Logger(){}Logger.prototype={' . implode(':function(){},', $m[1]) . ':function(){}}';

file_put_contents($dir . '/NullLogger.js', $js);

die("Done.\n");