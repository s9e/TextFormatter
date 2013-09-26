<?php

// Get the autoloader
include __DIR__ . '/../src/s9e/TextFormatter/autoloader.php';

// Use the Forum bundle. It supports BBCodes, emoticons and autolinking
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

// Original text
$text = "Hello, [i]world[/i] :)\nFind more examples in the [url=https://github.com/s9e/TextFormatter/tree/master/docs/Cookbook]Cookbook[/url].";

// XML representation, that's what you should store in your database
$xml  = TextFormatter::parse($text);

// HTML rendering, that's what you display to the user
$html = TextFormatter::render($xml, ['EMOTICONS_PATH' => '/path/to/emoticons']);

echo $html, "\n";
