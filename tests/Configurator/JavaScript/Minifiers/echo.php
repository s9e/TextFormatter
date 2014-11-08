<?php

$cmd = implode(' ', $_SERVER['argv']);

if (preg_match('(js_output_file (.*?\\.js))', $cmd, $m))
{
	file_put_contents($m[1], $cmd);
}