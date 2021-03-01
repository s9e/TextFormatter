#!/usr/bin/php
<?php

include __DIR__ . '/../vendor/autoload.php';

class Proxy extends s9e\TextFormatter\Configurator\Collections\Ruleset
{
	public function getRules()
	{
		return $this->rules;
	}
}

$args = [
	'addBooleanRule'  => 'bool $bool = true',
	'addTargetedRule' => 'string $tagName'
];

$doc  = '';
foreach ((new Proxy)->getRules() as $ruleName => $methodName)
{
	$doc .= "\n* @method void " . $ruleName . '(' . $args[$methodName] . ')';
}

$filepath = realpath(__DIR__ . '/../src/Configurator/Collections/Ruleset.php');
$file     = file_get_contents($filepath);

$old  = $file;
$file = preg_replace_callback(
	'((/\\*\\*)(?:\\n\\* @method.*)+)',
	function ($m) use ($doc)
	{
		return $m[1] . $doc;
	},
	$file
);

if ($file !== $old)
{
	echo "Patched $filepath\n";

	file_put_contents($filepath, $file);
}

die("Done.\n");