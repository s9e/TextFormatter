#!/usr/bin/php
<?php

$match   = ['(,\\s*"scripts"[^}]+\\})'];
$replace = [''];

if (isset($_SERVER['argv'][1]))
{
	$match[]   = '(("php":\\s*"\\D*)[.\\d]++)';
	$replace[] = '${1}' . $_SERVER['argv'][1];
}

$filepath = __DIR__ . '/../../composer.json';
file_put_contents($filepath, preg_replace($match, $replace, file_get_contents($filepath)));