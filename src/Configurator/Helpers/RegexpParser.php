<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
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
						/**
						* This is an option (?i) so we skip past the right parenthesis
						*/
						$ret['tokens'][] = array(
							'pos'     => $pos,
							'len'     => strlen($m[0]),
							'type'    => 'option',
							'options' => $m[1]
						);

						$pos += strlen($m[0]);
						break;
					}

					/**
					* This should be a subpattern, we just have to sniff which kind
					*/
					if (preg_match("#(?J)\\(\\?(?:P?<(?<name>[a-z_0-9]+)>|'(?<name>[a-z_0-9]+)')#A", $regexp, $m, \PREG_OFFSET_CAPTURE, $pos))
					{
						/**
						* This is a named capture
						*/
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
						/**
						* This is a non-capturing subpattern (?:xxx)
						*/
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
						/**
						* This is a non-capturing subpattern with atomic grouping (?>x+)
						*/
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
						/**
						* This is an assertion
						*/
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
						/**
						* This should be a normal capture
						*/
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

					/**
					* Add the key to this token to its matching token and capture this subpattern's
					* content
					*/
					$k = array_pop($openSubpatterns);
					$startToken =& $ret['tokens'][$k];
					$startToken['endToken'] = count($ret['tokens']);
					$startToken['content']  = substr(
						$regexp,
						$startToken['pos'] + $startToken['len'],
						$pos - ($startToken['pos'] + $startToken['len'])
					);

					/**
					* Look for quantifiers after the subpattern, e.g. (?:ab)++
					*/
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
}