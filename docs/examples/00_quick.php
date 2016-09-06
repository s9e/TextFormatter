<?php

// Get the autoloader (unless you have already loaded Composer's)
include __DIR__ . '/../../src/autoloader.php';

// Use the Forum bundle. It supports BBCodes, emoticons and autolinking
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

// Original text
$text = "Hello, [i]world[/i] :)\nFind more examples in the [url=https://github.com/s9e/TextFormatter/tree/master/docs/Cookbook]Cookbook[/url].";

// XML representation, that's what you should store in your database
$xml  = TextFormatter::parse($text);

// HTML rendering, that's what you display to the user
$html = TextFormatter::render($xml);

echo $html, "\n";

// Outputs:
//
// Hello, <i>world</i> <img src="/path/to/emoticons/smile.png" alt=":)"><br>
// Find more examples in the <a href="https://github.com/s9e/TextFormatter/tree/master/docs/Cookbook">Cookbook</a>.
