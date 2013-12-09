#!/usr/bin/php
<?php

/*

This script automatically micro-optimizes sources by trading readability for better opcodes. For
instance, strpos() looks better than \strpos() but used in a namespace they produce different
opcodes: DO_FCALL_BY_NAME and DO_FCALL respectively. The latter is simpler than the former but their
performance is nearly identical so it doesn't warrant making it a rule to systematically use FQNs
for functions in the master codebase. The release/* branches, however, are not maintained by hand
and are not meant to be read by anyone. In this setting, it makes sense to uglify the code a little
if it ends up making it better.

This script is called by scripts/travis/setup.sh so that tests are run on the "optimized" codebase.

*/

namespace s9e\TextFormatter\Build\Optimize;

function optimizeDir($dir)
{
	foreach (glob($dir . '/*', GLOB_ONLYDIR) as $sub)
	{
		optimizeDir($sub);
	}

	foreach (glob($dir . '/*.php') as $filepath)
	{
		optimizeFile($filepath);
	}
}

function optimizeFile($filepath)
{
	$tokens = token_get_all(file_get_contents($filepath));
	$save   = false;

	// strpos() => \strpos()
	foreach ($tokens as $i => &$token)
	{
		if ($token !== '(')
		{
			continue;
		}

		if ($tokens[$i - 1][0] !== T_STRING)
		{
			continue;
		}

		// Skip if preceded by \ -> or ::
		if ($tokens[$i - 2][0] === T_NS_SEPARATOR
		 || $tokens[$i - 2][0] === T_OBJECT_OPERATOR
		 || $tokens[$i - 2][0] === T_PAAMAYIM_NEKUDOTAYIM)
		{
			continue;
		}

		// Skip if preceded by function or new
		if ($tokens[$i - 3][0] === T_FUNCTION
		 || $tokens[$i - 3][0] === T_NEW)
		{
			continue;
		}

		$tokens[$i - 1][1] = '\\' . $tokens[$i - 1][1];
		$save = true;
	}
	unset($token);

	// PREG_SET_ORDER => \PREG_SET_ORDER
	foreach ($tokens as $i => &$token)
	{
		if ($token[0] !== T_STRING
		 || !preg_match('(^[A-Z_]+$)D', $token[1])
		 || !defined($token[1]))
		{
			continue;
		}

		// Skip if preceded by \ -> or ::
		if ($tokens[$i - 1][0] === T_NS_SEPARATOR
		 || $tokens[$i - 1][0] === T_OBJECT_OPERATOR
		 || $tokens[$i - 1][0] === T_PAAMAYIM_NEKUDOTAYIM)
		{
			continue;
		}

		// Skip if preceded by class, const, function, interface, new, trait or use
		if ($tokens[$i - 2][0] === T_CLASS
		 || $tokens[$i - 2][0] === T_CONST
		 || $tokens[$i - 2][0] === T_FUNCTION
		 || $tokens[$i - 2][0] === T_INTERFACE
		 || $tokens[$i - 2][0] === T_NEW
		 || $tokens[$i - 2][0] === T_TRAIT
		 || $tokens[$i - 2][0] === T_USE)
		{
			continue;
		}

		$token[1] = '\\' . $token[1];
		$save = true;
	}
	unset($token);

	// if (1) { foo(); } => if (1) foo();
	foreach ($tokens as $i => &$token)
	{
	}
	unset($token);

	if ($save)
	{
		$php = '';
		foreach ($tokens as $token)
		{
			$php .= (is_array($token)) ? $token[1] : $token;
		}

		file_put_contents($filepath, $php);
		echo "Optimized $filepath\n";
	}
}

// PHP 5.3 compatibility
if (!defined('T_TRAIT'))
{
	define('T_TRAIT', 357);
}

optimizeDir(realpath(__DIR__ . '/../../src/s9e/TextFormatter'));
