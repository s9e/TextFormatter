#!/usr/bin/php
<?php

$regexp = '(src="(?<src>[^"]++).*?data-hljs-url="(?<url>[^"]++).*?integrity="(?<integrity>[^"]++))s';
$file   = file_get_contents('https://raw.githubusercontent.com/s9e/hljs-loader/master/README.md');
if (!preg_match($regexp, $file, $m))
{
	die("Cannot parse hljs-loader README.md\n");
}

$filepath = __DIR__ . '/../src/Plugins/BBCodes/Configurator/repository.xml';
$old      = file_get_contents($filepath);

$new = $old;
$new = preg_replace('(https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@.*?/build/)', $m['url'], $new);
$new = preg_replace('(https://cdn.jsdelivr.net/gh/s9e/hljs-loader@.*?/loader.min.js)', $m['src'], $new);
$new = preg_replace('(<xsl:attribute name="integrity">\\K[^<]++)', $m['integrity'], $new);

if ($new !== $old)
{
	file_put_contents($filepath, $new);
	echo "Patched $filepath.\n";
}