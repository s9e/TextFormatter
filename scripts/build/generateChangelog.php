<?php

$newVersion  = $_SERVER['argv'][1];
$oldCommitId = $_SERVER['argv'][2];
$newCommitId = $_SERVER['argv'][3];

$entries = [];
$lines   = explode("\n", trim(file_get_contents('php://stdin')));

$types = [
	'Ignore'  => '((?:#ignore|#tests?|ci skip|: \w+ed \w*\s*test|(?:build|pre-commit|release|travis) script)|travis config)i',
	'Added'   => '(\\bAdded\\b)i',
	'Fixed'   => '(\\bFixed\\b)i',
	'Removed' => '(\\bRemoved\\b)i',
	'Changed' => '()'
];

foreach ($lines as $line)
{
	$pos     = strpos($line, ' ');
	$sha1    = substr($line, 0, $pos);
	$subject = substr($line, $pos + 1);

	foreach ($types as $type => $regexp)
	{
		if (preg_match($regexp, $subject, $m))
		{
			break;
		}
	}

	$entries[$type][$sha1] = $subject;
}

$header = $newVersion . ' (' . gmdate('Y-m-d') . ')';
echo $header, "\n", str_repeat('=', strlen($header)), "\n\n";

echo "[Full commit log](https://github.com/s9e/TextFormatter/compare/$oldCommitId...$newCommitId)\n";

foreach (['Added', 'Removed', 'Fixed', 'Changed'] as $type)
{
	if (empty($entries[$type]))
	{
		continue;
	}

	echo "\n### ", $type, "\n\n";

	asort($entries[$type]);
	foreach ($entries[$type] as $sha1 => $subject)
	{
		echo " - `$sha1` $subject\n";
	}
}
echo "\n\n";