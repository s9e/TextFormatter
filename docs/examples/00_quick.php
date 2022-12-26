<?php

// Get the autoloader
include __DIR__ . '/../../vendor/autoload.php';

// Use the Forum bundle. It supports BBCodes, emoticons and autolinking
use s9e\TextFormatter\Bundles\Forum as TextFormatter;

// Original text
$text = "Hello, [i]world[/i] :)\nFind more examples in the [url=https://s9etextformatter.readthedocs.io/]documentation[/url].";

// XML representation, that's what you should store in your database
$xml  = TextFormatter::parse($text);

// HTML rendering, that's what you display to the user
$html = TextFormatter::render($xml);

echo $html, "\n";

// Outputs:
//
// Hello, <i>world</i> <img alt=":)" class="emoji" draggable="false" src="https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/1f642.svg"><br>
// Find more examples in the <a href="https://s9etextformatter.readthedocs.io/">documentation</a>.
