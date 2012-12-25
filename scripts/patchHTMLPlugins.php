#!/usr/bin/php
<?php

//die("Not currently used.\n");

if (!file_exists('/tmp/entities-unicode.inc'))
{
	copy(
		'compress.zlib://http://svn.whatwg.org/webapps/entities-unicode.inc',
		'/tmp/entities-unicode.inc',
		stream_context_create(array(
			'http' => array(
				'header' => "Accept-Encoding: gzip,deflate"
			)
		))
	);
}

if (!preg_match_all('#<td>\\s*<code title="[^"]*">([^<]+)</code>\\s*</td>\\s*<td>([^<]+)</td>#', file_get_contents('/tmp/entities-unicode.inc'), $matches, PREG_SET_ORDER))
{
	die('Could not parse /tmp/entities-unicode.inc');
}

function cp_to_utf8($cp)
{
	if ($cp > 0xffff)
	{
		return chr(0xf0 | ($cp >> 18)) . chr(0x80 | (($cp >> 12) & 0x3f)) . chr(0x80 | (($cp >> 6) & 0x3f)) . chr(0x80 | ($cp & 0x3f));
	}
	elseif ($cp > 0x7ff)
	{
		return chr(0xe0 | ($cp >> 12)) . chr(0x80 | (($cp >> 6) & 0x3f)) . chr(0x80 | ($cp & 0x3f));
	}
	elseif ($cp > 0x7f)
	{
		return chr(0xc0 | ($cp >> 6)) . chr(0x80 | ($cp & 0x3f));
	}
	else
	{
		return chr($cp);
	}
}

$table = array();
foreach ($matches as $m)
{
	$entity = trim($m[1], '&;');

	// A single entity can represent multiple Unicode chars, e.g. &nvap;
	preg_match_all('#U\\+0*([0-9A-F]+)#i', $m[2], $_m);
	$glyph = implode('', array_map('cp_to_utf8', array_map('hexdec', $_m[1])));

	if (html_entity_decode("&$entity;", ENT_QUOTES, 'UTF-8') !== $glyph)
	{
		$table[$entity] = $glyph;
	}
}