<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Parser,
    s9e\TextFormatter\PluginParser;

class BBCodesParser extends PluginParser
{
	/**
	* @var array List of available BBCodes
	*/
	protected $bbcodes;

	public function setUp()
	{
		$this->bbcodes = $this->config['bbcodes'];
	}

	public function getTags($text, array $matches)
	{
		$tags = array();

		$textLen = strlen($text);

		foreach ($matches as $m)
		{
			$bbcodeName = strtoupper($m[1][0]);

			if (!isset($this->bbcodes[$bbcodeName]))
			{
				// Not a known BBCode
				continue;
			}

			$bbcodeConfig = $this->bbcodes[$bbcodeName];
			$tagName      = $bbcodeConfig['tagName'];

			/**
			* @var Position of the first character of current BBCode, which should be a [
			*/
			$lpos = $m[0][1];

			/**
			* @var Position of the last character of current BBCode, starts as the position of
			*      the =, ] or : char, then moves to the right as the BBCode is parsed
			*/
			$rpos = $lpos + strlen($m[0][0]);

			/**
			* @var Attributes parsed from the text
			*/
			$attrs = array();

			/**
			* Check for BBCode suffix
			*
			* Used to skip the parsing of closing BBCodes, e.g.
			*   [code:1][code]type your code here[/code][/code:1]
			*
			*/
			if ($text[$rpos] === ':')
			{
				/**
				* [code:1] or [/code:1]
				* $suffix = ':1'
				*/
				$spn     = strspn($text, '1234567890', 1 + $rpos);
				$suffix  = substr($text, $rpos, 1 + $spn);
				$rpos   += 1 + $spn;
			}
			else
			{
				$suffix  = '';
			}

			if ($m[0][0][1] === '/')
			{
				if ($text[$rpos] !== ']')
				{
					$this->parser->log('warning', array(
						'pos'    => $rpos,
						'len'    => 1,
						'msg'    => 'Unexpected character: expected %1$s found %2$s',
						'params' => array(']', $text[$rpos])
					));
					continue;
				}

				$type = Parser::END_TAG;
			}
			else
			{
				$type       = Parser::START_TAG;
				$wellFormed = false;
				$firstPos   = $rpos;

				while ($rpos < $textLen)
				{
					$c = $text[$rpos];

					if ($c === ']' || $c === '/')
					{
						/**
						* We're closing this tag
						*/
						if ($c === '/')
						{
							/**
							* Self-closing tag, e.g. [foo/]
							*/
							$type = Parser::SELF_CLOSING_TAG;
							++$rpos;

							if ($rpos === $textLen)
							{
								// text ends with [some tag/
								continue 2;
							}

							$c = $text[$rpos];
							if ($c !== ']')
							{
								$this->parser->log('warning', array(
									'pos'    => $rpos,
									'len'    => 1,
									'msg'    => 'Unexpected character: expected %1$s found %2$s',
									'params' => array(']', $c)
								));
								continue 2;
							}
						}

						$wellFormed = true;
						break;
					}

					if ($c === ' ')
					{
						++$rpos;
						continue;
					}

					/**
					* Capture the attribute name
					*/
					$spn = strspn($text, 'abcdefghijklmnopqrstuvwxyz_0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ-', $rpos);

					if ($spn)
					{
						if ($rpos + $spn >= $textLen)
						{
							$this->parser->log('debug', array(
								'pos' => $rpos,
								'len' => $spn,
								'msg' => 'Attribute name seems to extend till the end of text'
							));
							continue 2;
						}

						$attrName = strtolower(substr($text, $rpos, $spn));
						$rpos += $spn;
					}
					else
					{
						if ($c === '='
						 && $rpos === $firstPos)
						{
							/**
							* [quote=
							*
							* This is the default param. If there's no default param, we issue a
							* warning and reuse the BBCode's name instead.
							*/
							if (isset($bbcodeConfig['defaultAttr']))
							{
								$attrName = $bbcodeConfig['defaultAttr'];
							}
							else
							{
								$attrName = strtolower($bbcodeName);

								$this->parser->log('debug', array(
									'pos'    => $rpos,
									'len'    => 1,
									'msg'    => 'BBCode %1$s does not have a default attribute, using BBCode name as attribute name',
									'params' => array($bbcodeName)
								));
							}
						}
						else
						{
							$this->parser->log('warning', array(
								'pos'    => $rpos,
								'len'    => 1,
								'msg'    => 'Unexpected character %s',
								'params' => array($c)
							));
							continue 2;
						}
					}

					if ($text[$rpos] !== '=')
					{
						/**
						* It's an attribute name not followed by an equal sign, let's just
						* ignore it
						*/
						continue;
					}

					/**
					* Move past the = and make sure we're not at the end of the text
					*/
					if (++$rpos >= $textLen)
					{
						$this->parser->log('debug', array(
							'msg' => 'Attribute definition seems to extend till the end of text'
						));
						continue 2;
					}

					$c = $text[$rpos];
					if ($c === '"' || $c === "'")
					{
						// This is where the value starts
						$valuePos = $rpos + 1;

						while (1)
						{
							// Move past the quote
							++$rpos;

							// Look for the next quote
							$rpos = strpos($text, $c, $rpos);

							if ($rpos === false)
							{
								// No matching quote. Apparently that string never ends...
								$this->parser->log('warning', array(
									'pos' => $valuePos - 1,
									'len' => 1,
									'msg' => 'Could not find matching quote'
								));
								continue 3;
							}

							// Test for an odd number of backslashes before this character
							$n = 0;
							while ($text[$rpos - ++$n] === '\\');

							if ($n % 2)
							{
								// If $n is odd, it means there's an even number of backslashes so
								// we can exit this loop
								break;
							}
						}

						// Unescape special characters ' " and \
						$value = preg_replace(
							'#\\\\([\\\\\'"])#',
							'$1',
							substr($text, $valuePos, $rpos - $valuePos)
						);

						// Skip past the closing quote
						++$rpos;
					}
					else
					{
						$spn   = strcspn($text, "] \n\r", $rpos);
						$value = substr($text, $rpos, $spn);

						$rpos += $spn;
					}

					$attrs[$attrName] = $value;
				}

				if (!$wellFormed)
				{
					continue;
				}

				$usesContent = false;

				if ($type === Parser::START_TAG
				 && isset($bbcodeConfig['contentAttrs']))
				{
					/**
					* Capture the content of that tag and use it as attribute value
					*
					* @todo insert the corresponding closing tag now, to ensure that we captured
					*       exactly what will end up being this tag pair's content. Would make a
					*       difference in [a][b="[/a]"][/b][/a]
					*
					* @todo perhaps disable all BBCodes when the content is used as param? how?
					*/
					foreach ($bbcodeConfig['contentAttrs'] as $attrName)
					{
						if (!isset($attrs[$attrName]))
						{
							$pos = stripos($text, '[/' . $bbcodeName . $suffix . ']', $rpos);

							if ($pos)
							{
								$attrs[$attrName] = substr($text, 1 + $rpos, $pos - (1 + $rpos));

								$usesContent = true;
							}
						}
					}
				}
			}

			if ($type === Parser::START_TAG
			 && !$usesContent
			 && !empty($bbcodeConfig['autoClose']))
			{
				$endTag = '[/' . $bbcodeName . $suffix . ']';

				/**
				* Make sure that the start tag isn't immediately followed by an endtag
				*/
				if (strtoupper(substr($text, 1 + $rpos, strlen($endTag))) !== $endTag)
				{
					$type |= Parser::END_TAG;
				}
			}

			$tags[] = array(
				'name'    => $tagName,
				'pos'     => $lpos,
				'len'     => $rpos + 1 - $lpos,
				'type'    => $type,
				'tagMate' => ($suffix) ? substr($suffix, 1) : '',
				'attrs'   => $attrs
			);
		}

		return $tags;
	}
}