#!/usr/bin/php
<?php

$regexp = '(src="(?<src>[^"]++).*?data-hljs-url="(?<url>[^"]++).*?integrity="(?<integrity>[^"]++))s';
$file   = file_get_contents('https://raw.githubusercontent.com/s9e/hljs-loader/master/README.md');
if (!preg_match($regexp, $file, $m))
{
	die("Cannot parse hljs-loader README.md\n");
}

array_map(
	function ($filepath) use ($m)
	{
		$replacements = [
			'(https://cdn.jsdelivr.net/gh/highlightjs/cdn-release@.*?/build/)' => $m['url'],
			'(https://cdn.jsdelivr.net/gh/s9e/hljs-loader@.*?/loader.min.js)'  => $m['src'],
			'(<xsl:attribute name="integrity">\\K[^<]++)'                      => $m['integrity'],
			'( integrity="\\K[^"]++(?=.*?hljs))'                               => $m['integrity']
		];

		$old = file_get_contents($filepath);
		$new = preg_replace(array_keys($replacements), $replacements, $old);
		if ($new !== $old)
		{
			file_put_contents($filepath, $new);
			echo "Patched $filepath.\n";
		}
	},
	[
		__DIR__ . '/../src/Plugins/BBCodes/Configurator/repository.xml',
		__DIR__ . '/../tests/Bundles/data/Forum/016.html',
		__DIR__ . '/../tests/Bundles/data/Forum/017.html',
		__DIR__ . '/../tests/Bundles/data/Forum/026.html',
		__DIR__ . '/../tests/Plugins/BBCodes/BBCodesTest.php'
	]
);