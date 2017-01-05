<?php

/*

Below are a few informal benchmarks, meant to be run in CLI. Loading the parser and renderer
represents the worst case scenario of parsing or rendering a text in a webserver with no opcode
cache. The first parsing and rendering measures the time it takes to load extra plugins and set up
the internal data structures. All subsequent parsings and renderings should be faster than the
first. Parsing and rendering plain text is a special case that uses code optimized for this task. In
general, whether it's blogs or forums a majority of user content is plain text with no HTML, BBCodes
or emoticons.

All times are expressed in microseconds. (1/1,000,000th of a second)

*/

include __DIR__ . '/../../vendor/autoload.php';

use s9e\TextFormatter\Bundles\Forum as TextFormatter;

echo "All times are expressed in microseconds. (1 millionth of a second)\n\n";

$t1 = microtime(true);
TextFormatter::getParser();
$t2 = microtime(true);
TextFormatter::getRenderer();
$t3 = microtime(true);

printf("%6s µs - Loading parser\n", round(1000000 * ($t2 - $t1)));
printf("%6s µs - Loading renderer\n", round(1000000 * ($t3 - $t2)));

$text = 'Hello, [i]world[/i] :)';
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("%6s µs - Parsing rich text for the first time\n", round(1000000 * ($t2 - $t1)));
printf("%6s µs - Rendering rich text for the first time\n", round(1000000 * ($t3 - $t2)));

$text = 'Hello, [i]world[/i] :)';
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("%6s µs - Parsing rich text for the second time\n", round(1000000 * ($t2 - $t1)));
printf("%6s µs - Rendering rich text for the second time\n", round(1000000 * ($t3 - $t2)));

$text = 'Hello, world!';
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("%6s µs - Parsing plain text\n", round(1000000 * ($t2 - $t1)));
printf("%6s µs - Rendering plain text\n", round(1000000 * ($t3 - $t2)));

$text = str_repeat("A line of [b]rich[/b] text\n", 200);
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("%6s µs - Parsing 200 lines of rich text\n", round(1000000 * ($t2 - $t1)));
printf("%6s µs - Rendering 200 lines of rich text\n", round(1000000 * ($t3 - $t2)));

$text = str_repeat(":) ", 1000);
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("%6s µs - Parsing 1000 emoticons\n", round(1000000 * ($t2 - $t1)));
printf("%6s µs - Rendering 1000 emoticons\n", round(1000000 * ($t3 - $t2)));
