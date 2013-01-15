<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Configurator\Helpers;

use RuntimeException;

abstract class RegexpParser
{
	/**
	* @param  string $regexp
	* @return array
	*/
	public static function parse($regexp)
	{
		if (!preg_match('#^(.)(.*?)\\1([a-zA-Z]*)$#Ds', $regexp, $m))
		{
			throw new RuntimeException('Could not parse regexp delimiters');
		}

		$ret = array(
			'delimiter' => $m[1],
			'modifiers' => $m[3],
			'regexp'    => $m[2],
			'tokens'    => array()
		);

		$regexp = $m[2];

		$openSubpatterns = array();

		$pos = 0;
		$regexpLen = strlen($regexp);

		while ($pos < $regexpLen)
		{
			switch ($regexp[$pos])
			{
				case '\\':
					// skip next character
					$pos += 2;
					break;

				case '[':
					if (!preg_match('#\\[(.*?(?<!\\\\)(?:\\\\\\\\)*+)\\]((?:[\\+\\*]\\+?)?)#', $regexp, $m, 0, $pos))
					{
						throw new RuntimeException('Could not find matching bracket from pos ' . $pos);
					}

					$ret['tokens'][] = array(
						'pos'         => $pos,
						'len'         => strlen($m[0]),
						'type'        => 'characterClass',
						'content'     => $m[1],
						'quantifiers' => $m[2]
					);

					$pos += strlen($m[0]);
					break;

				case '(';
					if (preg_match('#\\(\\?([a-z]*)\\)#i', $regexp, $m, 0, $pos))
					{
						// This is an option (?i) so we skip past the right parenthesis
						$ret['tokens'][] = array(
							'pos'     => $pos,
							'len'     => strlen($m[0]),
							'type'    => 'option',
							'options' => $m[1]
						);

						$pos += strlen($m[0]);
						break;
					}

					// This should be a subpattern, we just have to sniff which kind
					if (preg_match("#(?J)\\(\\?(?:P?<(?<name>[a-z_0-9]+)>|'(?<name>[a-z_0-9]+)')#A", $regexp, $m, \PREG_OFFSET_CAPTURE, $pos))
					{
						// This is a named capture
						$tok = array(
							'pos'  => $pos,
							'len'  => strlen($m[0][0]),
							'type' => 'capturingSubpatternStart',
							'name' => $m['name'][0]
						);

						$pos += strlen($m[0][0]);
					}
					elseif (preg_match('#\\(\\?([a-z]*):#iA', $regexp, $m, 0, $pos))
					{
						// This is a non-capturing subpattern (?:xxx)
						$tok = array(
							'pos'     => $pos,
							'len'     => strlen($m[0]),
							'type'    => 'nonCapturingSubpatternStart',
							'options' => $m[1]
						);

						$pos += strlen($m[0]);
					}
					elseif (preg_match('#\\(\\?>#iA', $regexp, $m, 0, $pos))
					{
						/* This is a non-capturing subpattern with atomic grouping "(?>x+)" */
						$tok = array(
							'pos'     => $pos,
							'len'     => strlen($m[0]),
							'type'    => 'nonCapturingSubpatternStart',
							'subtype' => 'atomic'
						);

						$pos += strlen($m[0]);
					}
					elseif (preg_match('#\\(\\?(<?[!=])#A', $regexp, $m, 0, $pos))
					{
						// This is an assertion
						$assertions = array(
							'='  => 'lookahead',
							'<=' => 'lookbehind',
							'!'  => 'negativeLookahead',
							'<!' => 'negativeLookbehind'
						);

						$tok = array(
							'pos'     => $pos,
							'len'     => strlen($m[0]),
							'type'    => $assertions[$m[1]] . 'AssertionStart'
						);

						$pos += strlen($m[0]);
					}
					elseif (preg_match('#\\(\\?#A', $regexp, $m, 0, $pos))
					{
						throw new RuntimeException('Unsupported subpattern type at pos ' . $pos);
					}
					else
					{
						// This should be a normal capture
						$tok = array(
							'pos'  => $pos,
							'len'  => 1,
							'type' => 'capturingSubpatternStart'
						);

						++$pos;
					}

					$openSubpatterns[] = count($ret['tokens']);
					$ret['tokens'][] = $tok;
					break;

				case ')':
					if (empty($openSubpatterns))
					{
						throw new RuntimeException('Could not find matching pattern start for right parenthesis at pos ' . $pos);
					}

					// Add the key to this token to its matching token and capture this subpattern's
					// content
					$k = array_pop($openSubpatterns);
					$startToken =& $ret['tokens'][$k];
					$startToken['endToken'] = count($ret['tokens']);
					$startToken['content']  = substr(
						$regexp,
						$startToken['pos'] + $startToken['len'],
						$pos - ($startToken['pos'] + $startToken['len'])
					);

					// Look for quantifiers after the subpattern, e.g. (?:ab)++
					$spn = strspn($regexp, '+*', 1 + $pos);
					$quantifiers = substr($regexp, 1 + $pos, $spn);

					$ret['tokens'][] = array(
						'pos'  => $pos,
						'len'  => 1 + $spn,
						'type' => substr($startToken['type'], 0, -5) . 'End',
						'quantifiers' => $quantifiers
					);

					unset($startToken);

					$pos += 1 + $spn;
					break;

				default:
					++$pos;
			}
		}

		if (!empty($openSubpatterns))
		{
			throw new RuntimeException('Could not find matching pattern end for left parenthesis at pos ' . $ret['tokens'][$openSubpatterns[0]]['pos']);
		}

		return $ret;
	}

	/**
	* Generate a regexp that matches any single character allowed in a regexp
	*
	* This method will generate a regexp that can be used to determine whether a given character
	* could in theory be allowed in a string that matches the source regexp. For example, the source
	* regexp /^a+$/D would generate /a/ while /^foo\d+$/D would generate /[fo\d]/ whereas the regexp
	* /foo/ would generate // because it's not anchored so any characters could be found before or
	* after the literal "foo".
	*
	* @param  string $regexp Source regexp
	* @return string         Regexp that matches any single character allowed in the source regexp
	*/
	public static function getAllowedCharacterRegexp($regexp)
	{
		$def = self::parse($regexp);

		// If the regexp is uses the multiline modifier, this regexp can't match the whole string if
		// it contains newlines, so in effect it could allow any content
		if (strpos($def['modifiers'], 'm') !== false)
		{
			return '//';
		}

		// Test whether the regexp is anchored to match the whole input
		if (!self::isAnchored($def['regexp']))
		{
			return '//';
		}

		// Append a token to mark the end of the regexp
		$def['tokens'][] = array(
			'pos'  => strlen($def['regexp']),
			'len'  => 0,
			'type' => 'end'
		);

		$patterns = array();

		// Collect the literal portions of the source regexp
		$literal = '';
		$pos = 0;
		$depth = 0;
		foreach ($def['tokens'] as $token)
		{
			if ($token['pos'] > $pos)
			{
				// Append the content between last position and current position
				$literal .= substr($def['regexp'], $pos, $token['pos'] - $pos);
			}

			$pos = $token['pos'] + $token['len'];

			if ($token['type'] === 'characterClass')
			{
				$patterns[] = '[' . $token['content'] . ']';
			}
		}

		// Test for the presence of an unescaped dot
		if (preg_match('#(?<!\\\\)(?:\\\\\\\\)*\\.#', $literal))
		{
			if (strpos($def['modifiers'], 's') !== false
			 || strpos($literal, "\n") !== false)
			{
				return '//';
			}

			$patterns[] = '.';

			// Remove unescaped dots
			$literal = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\.#', '$1', $literal);
		}

		// Remove unescaped quantifiers *, + and ?
		$literal = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)[*+?]#', '$1', $literal);

		// Remove unescaped quantifiers {}
		$literal = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\{[^}]+\\}#', '$1', $literal);

		// Remove backslash assertions \b, \B, \A, \Z, \z and \G, as well as back references
		$literal = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)\\\\[bBAZzG1-9]#', '$1', $literal);

		// Remove unescaped ^, | and $
		$literal = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)[$^|]#', '$1', $literal);

		// Escape unescaped - and ] so they are safe to use in a character class
		$literal = preg_replace('#(?<!\\\\)((?:\\\\\\\\)*)([-^\\]])#', '$1\\\\$2', $literal);

		// If the regexp doesn't use PCRE_DOLLAR_ENDONLY, it could end with a \n
		if (strpos($def['modifiers'], 'D') === false)
		{
			$literal .= "\n";
		}

		// Add the literal portion of the regexp to the patterns, as a character class
		if ($literal !== '')
		{
			$patterns[] = '[' . $literal . ']';
		}

		// Build the allowed characters regexp
		$regexp = '/' . implode('|', $patterns) . '/';

		// Add the modifiers
		if (strpos($def['modifiers'], 'i') !== false)
		{
			$regexp .= 'i';
		}
		if (strpos($def['modifiers'], 'u') !== false)
		{
			$regexp .= 'u';
		}

		return $regexp;
	}

	/**
	* Test whether a regexp is anchored
	*
	* @param  string $regexp Regexp, without delimiters
	* @return bool
	*/
	protected static function isAnchored($regexp)
	{
		// Remove character classes
		$regexp = preg_replace(
			'#(?<!\\\\)(?:\\\\\\\\)*\\[.*?(?<!\\\\)((?:\\\\\\\\)*)\\]#s',
			'',
			$regexp
		);

		// Remove subpatterns and assertions
		$regexp = preg_replace(
			'#(?<!\\\\)(?:\\\\\\\\)*\\(.*?(?<!\\\\)((?:\\\\\\\\)*)\\)#s',
			'',
			$regexp
		);

		// Remove escaped |
		$regexp = preg_replace(
			'#(?<!\\\\)(?:\\\\\\\\)*\\\\\\|#',
			'',
			$regexp
		);

		foreach (explode('|', $regexp) as $pattern)
		{
			if (!preg_match('#^\\^.*(?<!\\\\)(?:\\\\\\\\)*\\$$#Ds', $pattern))
			{
				return false;
			}
		}

		return true;
	}
}