<?php

include __DIR__ . '/../../src/s9e/TextFormatter/autoloader.php';

use s9e\TextFormatter\Bundles\Forum as TextFormatter;

$t1 = microtime(true);
TextFormatter::getParser();
$t2 = microtime(true);
TextFormatter::getRenderer();
$t3 = microtime(true);

printf("Loading parser:        %.03f ms\n", 1000 * ($t2 - $t1));
printf("Loading renderer:      %.03f ms\n", 1000 * ($t3 - $t2));

$text = 'Hello, [i]world[/i] :)';
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("First parsing:         %.03f ms\n", 1000 * ($t2 - $t1));
printf("First rendering:       %.03f ms\n", 1000 * ($t3 - $t2));

$text = 'Hello, [i]world[/i] :)';
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("Second parsing:        %.03f ms\n", 1000 * ($t2 - $t1));
printf("Second rendering:      %.03f ms\n", 1000 * ($t3 - $t2));

$text = 'Hello, world!';
$t1   = microtime(true);
$xml  = TextFormatter::parse($text);
$t2   = microtime(true);
$html = TextFormatter::render($xml);
$t3   = microtime(true);

printf("Parsing plain text:    %.03f ms\n", 1000 * ($t2 - $t1));
printf("Rendering plain text:  %.03f ms\n", 1000 * ($t3 - $t2));

$arr  = array();
$text = 'Hello, [i]world[/i] :)';
$t1   = microtime(true);
for ($i = 0; $i < 10; ++$i)
{
	$arr[] = TextFormatter::parse($text);
}
$t2   = microtime(true);
$arr  = TextFormatter::renderMulti($arr);
$t3   = microtime(true);

printf("Parsing 10 messages:   %.03f ms\n", 1000 * ($t2 - $t1));
printf("Rendering 10 messages: %.03f ms\n", 1000 * ($t3 - $t2));
