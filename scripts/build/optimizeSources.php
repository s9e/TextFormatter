#!/usr/bin/php
<?php

namespace s9e\TextFormatter\Build\Optimize;

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

include __DIR__ . '/../../src/Configurator/RendererGenerators/PHP/ControlStructuresOptimizer.php';
$optimizer = new \s9e\TextFormatter\Configurator\RendererGenerators\PHP\ControlStructuresOptimizer;

function optimizeDir($dir, array $options = array())
{
	foreach (glob($dir . '/*.php') as $filepath)
	{
		optimizeFile($filepath, $options);
	}

	foreach (glob($dir . '/*', GLOB_ONLYDIR) as $sub)
	{
		optimizeDir($sub, $options);
	}
}

function optimizeFile($filepath, array $options = array())
{
	global $optimizer;

	$old     = file_get_contents($filepath);
	$changed = false;

	// Inline traits in the main Parser class
	if (strpos($filepath, 'src/Parser.php'))
	{
		// Replace "use" statements with the content of the trait
		$old = preg_replace_callback(
			'/^	use Parser\\\\(\\w+);/m',
			function ($m) use ($filepath)
			{
				$traitName = $m[1];

				// Capture the trait's content
				$traitFilepath = dirname($filepath) . '/Parser/' . $traitName . '.php';
				preg_match('#\\n{\\n(.*)\\n}$#s', file_get_contents($traitFilepath), $m);

				// Remove the trait's file
				unlink($traitFilepath);

				// Fix the test's code coverage annotation
				$testFilepath  = __DIR__ . '/../../tests/Parser/' . $traitName . 'Test.php';
				file_put_contents(
					$testFilepath,
					str_replace(
						'@covers s9e\\TextFormatter\\Parser\\' . $traitName,
						'@covers s9e\\TextFormatter\\Parser',
						file_get_contents($testFilepath)
					)
				);

				return $m[1] . "\n";
			},
			$old
		);

		// Traits live in the s9e\TextFormatter\Parser namespace whereas the Parser lives in the
		// s9e\TextFormatter namespace so we need to add a use statement for Tag
		if (strpos($old, 'use s9e\TextFormatter\Parser\Tag;') === false)
		{
			$old = str_replace("\nclass", "use s9e\\TextFormatter\\Parser\\Tag;\n\nclass", $old);
		}
	}

	$new = $optimizer->optimize($old);
	if ($new !== $old)
	{
		$changed = true;
	}

	$tokens = token_get_all($new);

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
		$changed = true;
	}
	unset($token);

	// PREG_SET_ORDER => \PREG_SET_ORDER
	foreach ($tokens as $i => &$token)
	{
		if ($token[0] !== T_STRING || !defined($token[1]))
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
		$changed  = true;
	}
	unset($token);

	if (!empty($options['removeComments']))
	{
		foreach ($tokens as $i => &$token)
		{
			if ($token[0] === T_DOC_COMMENT)
			{
				if (empty($options['removeDocblock']))
				{
					continue;
				}
			}
			elseif ($token[0] !== T_COMMENT)
			{
				continue;
			}

			if (empty($options['removeLicense']) && strpos($token[1], '@license') !== false)
			{
				continue;
			}

			if ($tokens[$i + 1][0] === T_WHITESPACE)
			{
				$tokens[$i + 1][1] = '';
			}

			$token[1] = '';
			$changed  = true;
		}
		unset($token);
	}

	if (!empty($options['convertDocblock']))
	{
		foreach ($tokens as $i => &$token)
		{
			if ($token[0] !== T_DOC_COMMENT)
			{
				continue;
			}

			$token[1] = '/' . substr($token[1], 2);
			$changed  = true;
		}
		unset($token);
	}

	if (!empty($options['removeWhitespace']))
	{
		if ($changed)
		{
			$tokens = token_get_all(rebuild($tokens));
		}

		foreach ($tokens as $i => &$token)
		{
			if ($token[0] !== T_WHITESPACE)
			{
				continue;
			}

			$before = (is_array($tokens[$i - 1])) ? $tokens[$i - 1][1] : $tokens[$i - 1];
			$after  = (is_array($tokens[$i + 1])) ? $tokens[$i + 1][1] : $tokens[$i + 1];

			$token[1] = (preg_match('/\\w$/', $before) && preg_match('/^\\w/', $after)) ? "\n" : '';
			$changed  = true;
		}
		unset($token);
	}

	if ($changed)
	{
		file_put_contents($filepath, rebuild($tokens));
		echo "\x1B[KOptimized $filepath\r";
	}
}

function rebuild(array &$tokens)
{
	$php = '';
	foreach ($tokens as $token)
	{
		$php .= (is_array($token)) ? $token[1] : $token;
	}

	return $php;
}

// PHP 5.3 compatibility
if (!defined('T_TRAIT'))
{
	define('T_TRAIT', 357);
}

// NOTE: none of those make any measurable difference with an opcode cache except for a slight
//       reduction in size when removing docblocks (which are cached by opcode caches because of
//       Reflection.) Minifying the source does reduce the time spent parsing it, so it does make a
//       difference without an opcode cache. However, those changes are too radical to be enabled
//       by default
$options = array(
	'convertDocblock'  => true,
	'removeComments'   => false,
	'removeDocblock'   => false,
	'removeLicense'    => false,
	'removeWhitespace' => false
);

optimizeDir(realpath(__DIR__ . '/../../src'), $options);
echo "\n";
