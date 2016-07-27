<?php

$dom = new DOMDocument;
$dom->load($_SERVER['argv'][1]);
foreach ($dom->getElementsByTagName('file') as $file)
{
	$statements        = 0;
	$coveredstatements = 0;
	foreach ($file->getElementsByTagName('line') as $line)
	{
		if ($line->getAttribute('type') !== 'stmt')
		{
			continue;
		}
		++$statements;
		if ($line->getAttribute('count') > 0)
		{
			++$coveredstatements;
		}
	}

	$metrics = $file->getElementsByTagName('metrics')->item(0);
	$metrics->setAttribute('statements',        $statements);
	$metrics->setAttribute('coveredstatements', $coveredstatements);
}
$dom->save($_SERVER['argv'][1]);