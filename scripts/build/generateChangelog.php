<?php

$oldVersion = $_SERVER['argv'][1];
$newVersion = $_SERVER['argv'][2];

$entries = [];
$lines   = explode("\n", trim(file_get_contents('php://stdin')));

$types = [
	'Ignore'  => '((?:#ignore|#tests|ci skip|(?:build|travis) script)(?<subject>))i',
	'New'     => '(^Added:?\\s*(?<subject>.*))i',
	'Fixed'   => '(^Fixed:?\\s*(?<subject>.*))i',
	'Removed' => '(^Removed:?\\s*(?<subject>.*))i',
	'Changed' => '(^(?:Updated:?\\s*)?(?<subject>.*))'
];

foreach ($lines as $line)
{
	if (strpos($line, '[ci skip]') !== false)
	{
		continue;
	}

	$pos     = strpos($line, ' ');
	$sha1    = substr($line, 0, $pos);
	$subject = substr($line, $pos + 1);

	foreach ($types as $type => $regexp)
	{
		if (preg_match($regexp, $subject, $m))
		{
			$subject = ucfirst($m['subject']);
			break;
		}
	}

	$entries[$type][$sha1] = $subject;
}

$version = json_decode(file_get_contents(__DIR__ . '/../../composer.json'))->version;
$date    = gmdate('Y-m-d');

$file = $version . ' (' . $date . ')';
$file .= "\n" . str_repeat('=', strlen($file)) . "\n\n";

$file .= "[Full commit log](https://github.com/s9e/TextFormatter/compare/$oldVersion...$newVersion)\n";

foreach (['New', 'Changed', 'Fixed'] as $type)
{
	if (empty($entries[$type]))
	{
		continue;
	}

	$file .= "\n" . $type . "\n" . str_repeat('-', strlen($type)) . "\n\n";

	asort($entries[$type]);
	foreach ($entries[$type] as $sha1 => $subject)
	{
		$file .= " - $sha1 $subject\n";
	}
}
$file .= "\n" . str_repeat('*', 80) . "\n\n";

$filepath = __DIR__ . '/../../CHANGELOG.md';
file_put_contents($filepath, $file . file_get_contents($filepath));