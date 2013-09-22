<?php

// Fake SMF environment
if (!defined('SMF'))
{
	define('SMF', 1);
}

if (!function_exists('isBrowser'))
{
	function isBrowser($arg)
	{
		return serialize($arg);
	}
}

if (!function_exists('timeformat'))
{
	function timeformat($arg)
	{
		return serialize($arg);
	}
}

global $modSettings, $scripturl, $txt, $user_info;

$modSettings = [
	'autoLinkUrls'        => 1,
	'disabledBBC'         => '',
	'enableBBC'           => 1,
	'enableEmbeddedFlash' => 1,
	'enablePostHTML'      => 1,
	'smileys_url'         => '/path/to/smileys',
];

$scripturl = '/path/to/smf';

$txt = [
	'code'        => 'C0d3',
	'code_select' => 'Sel3ct',
	'quote'       => 'Qu0te',
	'quote_from'  => 'Qu0te fr0m',
	'search_on'   => '0n'
];

$user_info = ['smiley_set' => 'set'];