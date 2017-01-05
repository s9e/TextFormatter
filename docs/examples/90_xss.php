<?php

include __DIR__ . '/../../vendor/autoload.php';

use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$text = '[url="javascript://example.org/%0Aalert(1)"]xss[/url]';
$xml  = TextFormatter::parse($text);
$html = TextFormatter::render($xml);

echo $html, "\n";

// Outputs:
//
// [url="javascript://example.org/%0Aalert(1)"]xss[/url]
