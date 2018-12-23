<?php

$newVersion  = $_SERVER['argv'][1];
$oldCommitId = $_SERVER['argv'][2];
$newCommitId = $_SERVER['argv'][3];

$entries = [];
$lines   = explode("\n", trim(file_get_contents('php://stdin')));

$types = [
	'Ignore'  => '(#ignore|#tests?|ci skip|(?:^|: )[-\w]+ed \\w*\\s*\\b(?:annotation|script|test)|travis config)i',
	'Fixed'   => '(\\bFixed\\b)i',
	'Added'   => '(\\bAdded\\b)i',
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

$apiChanges = file_get_contents(__DIR__ . '/../../docs/Internals/API_changes.md');
if (strpos($apiChanges, "## $newVersion\n") !== false)
{
	echo "**\xE2\x9A\xA0\xEF\xB8\x8F This release contains API changes. See [docs/Internals/API_changes.md](http://s9etextformatter.readthedocs.io/Internals/API_changes/#" . preg_replace('(\\D)', '', $newVersion) . ") for a description. \xE2\x9A\xA0\xEF\xB8\x8F**\n\n";
}

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