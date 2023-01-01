<?php declare(strict_types=1);

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\JavaScript;

use ValueError;

class Hasher
{
	/**
	* Generate a hash that matches the hashing algorithm used in render.js
	*
	* See hash() in render.js
	*
	* @param  string $text
	* @return int
	*/
	public static function quickHash(string $text): int
	{
		if (preg_match_all('(.)us', $text, $matches) === false)
		{
			throw new ValueError('Invalid UTF-8 string');
		}

		$codepoints = self::charsToCodepointsWithSurrogates($matches[0]);

		$pos = count($codepoints);
		$s1  = 0;
		$s2  = 0;
		while (--$pos >= 0)
		{
			$s1 = ($s1 + $codepoints[$pos]) % 0xFFFF;
			$s2 = ($s1 + $s2) % 0xFFFF;
		}
		$hash = ($s2 << 16) | $s1;

		// Convert to signed long
		if ($hash > 0x7FFFFFFF)
		{
			$hash -= 0x100000000;
		}

		return $hash;
	}

	/**
	* Convert a list of UTF-8 characters into a list of Unicode codepoint with surrogates
	*
	* @param  string[]  $chars
	* @return int[]
	*/
	protected static function charsToCodepointsWithSurrogates(array $chars): array
	{
		$codepoints = [];
		foreach ($chars as $char)
		{
			$cp = self::cp($char);
			if ($cp < 0x10000)
			{
				$codepoints[] = $cp;
			}
			else
			{
				$codepoints[] = 0xD7C0 + ($cp >> 10);
				$codepoints[] = 0xDC00 + ($cp & 0x3FF);
			}
		}

		return $codepoints;
	}

	/**
	* Compute and return the Unicode codepoint for given UTF-8 char
	*
	* @param  string  $char UTF-8 char
	* @return int
	*/
	protected static function cp(string $char): int
	{
		$cp = ord($char[0]);
		if ($cp >= 0xF0)
		{
			$cp = ($cp << 18) + (ord($char[1]) << 12) + (ord($char[2]) << 6) + ord($char[3]) - 0x3C82080;
		}
		elseif ($cp >= 0xE0)
		{
			$cp = ($cp << 12) + (ord($char[1]) << 6) + ord($char[2]) - 0xE2080;
		}
		elseif ($cp >= 0xC0)
		{
			$cp = ($cp << 6) + ord($char[1]) - 0x3080;
		}

		return $cp;
	}
}