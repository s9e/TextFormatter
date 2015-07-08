#!/usr/bin/php
<?php

$filepath = __DIR__ . '/../../composer.json';
file_put_contents(
	$filepath,
	preg_replace(
		'(,\\s*"scripts"[^}]+\\})',
		'',
		preg_replace(
			'(("php":\\s*"\\D*)[.\\d]++)',
			'${1}' . $_SERVER['argv'][1],
			file_get_contents($filepath)
		)
	)
);