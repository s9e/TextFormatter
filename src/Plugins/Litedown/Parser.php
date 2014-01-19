<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$hasEscapedChars = (strpos($text, '\\') !== false && preg_match('/\\\\[!")*[\\\\\\]^_`~]/', $text));

		// Encode escaped literals that have a special meaning otherwise, so that we don't have to
		// take them into account in regexps
		if ($hasEscapedChars)
		{
			$text = strtr(
				$text,
				[
					'\\!'  => "\x1B0",
					'\\"'  => "\x1B1",
					'\\)'  => "\x1B2",
					'\\*'  => "\x1B3",
					'\\['  => "\x1B4",
					'\\\\' => "\x1B5",
					'\\]'  => "\x1B6",
					'\\^'  => "\x1B7",
					'\\_'  => "\x1B8",
					'\\`'  => "\x1B9",
					'\\~'  => "\x1BA"
				]
			);
		}

		$lines = explode("\n", $text);
		foreach ($lines as $line)
		{
			$spn = strspn($line, ' -+*#>0123456789.');

			if (!$spn)
			{
				// NOTE: might be the continuation of a quote :\
				continue;
			}

			preg_match_all(
				'/> ?|\\* *\\* *\\*[* ]*$|- *- *-[- ]*$|[-*+] |\\d\\. |#+$/S',
				substr($line, 0, $spn),
				$matches
			);

			// Blockquote: ">" or "> "
			// List item:  "* "
			// List item:  "- "
			// List item:  "+ "
			// List item:  at least one digit followed by ". "
			// HR:         At least three * or - alone on a line, with any number of spaces between
			// Headings:   #+ alone on a line
			// Headings:   possibly any number of - or = alone on a line
			//
			// NOTE: apparently the only elements allowed after a list item are more list items
		}

		// Inline code
		if (strpos($text, '`') !== false)
		{
			preg_match_all(
				'/`[^\\x17`]++`/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);

			foreach ($matches[0] as list($match, $matchPos))
			{
				$matchLen = strlen($match);

				$this->parser->addTagPair('C', $matchPos, 1, $matchPos + $matchLen - 1, 1);

				// Overwrite the markup
				self::overwrite($text, $matchPos, $matchLen);
			}
		}

		// Images
		if (strpos($text, '![') !== false)
		{
			preg_match_all(
				'/!\\[([^\\x17\\]]++)] ?\\(([^\\x17 ")]++)(?> "([^\\x17"]*+)")?\\)/',
				$text,
				$matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			foreach ($matches as $m)
			{
				$matchPos    = $m[0][1];
				$matchLen    = strlen($m[0][0]);
				$contentLen  = strlen($m[1][0]);
				$startTagPos = $matchPos;
				$startTagLen = 2;
				$endTagPos   = $startTagPos + $startTagLen + $contentLen;
				$endTagLen   = $matchLen - $startTagLen - $contentLen;

				$startTag = $this->parser->addTagPair('IMG', $startTagPos, $startTagLen, $endTagPos, $endTagLen);
				$startTag->setAttribute('alt', self::decode($m[1][0], $hasEscapedChars));
				$startTag->setAttribute('src', self::decode($m[2][0], $hasEscapedChars));

				if (isset($m[3]))
				{
					$startTag->setAttribute('title', self::decode($m[3][0], $hasEscapedChars));
				}

				// Overwrite the markup
				self::overwrite($text, $matchPos, $matchLen);
			}
		}

		// Inline links
		if (strpos($text, '[') !== false)
		{
			preg_match_all(
				'/\\[([^\\x17\\]]++)] ?\\(([^\\x17)]++)\\)/',
				$text,
				$matches,
				PREG_SET_ORDER | PREG_OFFSET_CAPTURE
			);

			foreach ($matches as $m)
			{
				$matchPos    = $m[0][1];
				$matchLen    = strlen($m[0][0]);
				$contentLen  = strlen($m[1][0]);
				$startTagPos = $matchPos;
				$startTagLen = 1;
				$endTagPos   = $startTagPos + $startTagLen + $contentLen;
				$endTagLen   = $matchLen - $startTagLen - $contentLen;

				$this->parser->addTagPair('URL', $startTagPos, $startTagLen, $endTagPos, $endTagLen)
				             ->setAttribute('url', self::decode($m[2][0], $hasEscapedChars));
			}

			// Overwrite the markup without touching the link's text
			self::overwrite($text, $startTagPos, $startTagLen);
			self::overwrite($text, $endTagPos,   $endTagLen);
		}

		// Strikethrough
		if (strpos($text, '~~') !== false)
		{
			preg_match_all(
				'/~~[^\\x17]+?~~/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);

			foreach ($matches[0] as list($match, $matchPos))
			{
				$matchLen = strlen($match);

				$this->parser->addTagPair('DEL', $matchPos, 2, $matchPos + $matchLen - 2, 2);

				// Overwrite the markup
				self::overwrite($text, $matchPos, $matchLen);
			}
		}

		// Superscript
		if (strpos($text, '^') !== false)
		{
			preg_match_all(
				'/\\^[^\\x17\\s]++/',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);

			foreach ($matches[0] as list($match, $matchPos))
			{
				$matchLen    = strlen($match);
				$startTagPos = $matchPos;
				$endTagPos   = $matchPos + $matchLen;

				$parts = explode('^', $match);
				unset($parts[0]);

				foreach ($parts as $part)
				{
					$this->parser->addTagPair('SUP', $startTagPos, 1, $endTagPos, 0);
					$startTagPos += 1 + strlen($part);
				}
			}
		}

		// Emphasis
		foreach (['*' => '/\\*+/', '_' => '/_+/'] as $c => $regexp)
		{
			if (strpos($text, $c) === false)
			{
				continue;
			}

			$buffered = 0;
			$breakPos = strpos($text, "\x17");

			preg_match_all($regexp, $text, $matches, PREG_OFFSET_CAPTURE);
			foreach ($matches[0] as list($match, $matchPos))
			{
				$matchLen = strlen($match);

				// Test whether we've just passed the limits of a block
				if ($breakPos !== false && $matchPos > $breakPos)
				{
					// Reset the buffer then look for the next break
					$buffered = 0;
					$breakPos = strpos($text, "\x17", $matchPos);
				}

				if ($matchLen >= 3)
				{
					// Number of characters left unconsumed
					$remaining = $matchLen;

					if ($buffered < 3)
					{
						$strongEndPos = $emEndPos = $matchPos;
					}
					else
					{
						// Determine the order of strong's and em's end tags
						if ($emPos < $strongPos)
						{
							// If em starts before strong, it must end after it
							$strongEndPos = $matchPos;
							$emEndPos     = $matchPos + 2;
						}
						else
						{
							// Make strong end after em
							$strongEndPos = $matchPos + 1;
							$emEndPos     = $matchPos;

							// If the buffer holds three consecutive characters and the order of
							// strong and em is not defined we push em inside of strong
							if ($strongPos === $emPos)
							{
								$emPos += 2;
							}
						}
					}

					// 2 or 3 means a strong is buffered
					// Strong uses the outer characters
					if ($buffered & 2)
					{
						$this->parser->addTagPair('STRONG', $strongPos, 2, $strongEndPos, 2);
						$remaining -= 2;
					}

					// 1 or 3 means an em is buffered
					// Em uses the inner characters
					if ($buffered & 1)
					{
						$this->parser->addTagPair('EM', $emPos, 1, $emEndPos, 1);
						--$remaining;
					}

					if (!$remaining)
					{
						$buffered = 0;
					}
					else
					{
						$buffered = min($remaining, 3);

						if ($buffered & 1)
						{
							$emPos = $matchPos + $matchLen - $buffered; 
						}

						if ($buffered & 2)
						{
							$strongPos = $matchPos + $matchLen - $buffered; 
						}
					}
				}
				elseif ($matchLen === 2)
				{
					if ($buffered === 3 && $strongPos === $emPos)
					{
						$this->parser->addTagPair('STRONG', $emPos + 1, 2, $matchPos, 2);
						$buffered = 1;
					}
					elseif ($buffered & 2)
					{
						$this->parser->addTagPair('STRONG', $strongPos, 2, $matchPos, 2);
						$buffered -= 2;
					}
					else
					{
						$buffered += 2;
						$strongPos = $matchPos;
					}
				}
				else
				{
					// Ignore single underscores when they are between alphanumeric ASCII chars
					if ($c === '_'
					 && $matchPos > 0
					 && strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $text[$matchPos - 1]) > 0
					 && $matchPos < strlen($text) - 1
					 && strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $text[$matchPos + 1]) > 0)
					{
						 continue;
					}

					if ($buffered === 3 && $strongPos === $emPos)
					{
						$this->parser->addTagPair('EM', $strongPos + 2, 1, $matchPos, 1);
						$buffered = 2;
					}
					elseif ($buffered & 1)
					{
						$this->parser->addTagPair('EM', $emPos, 1, $matchPos, 1);
						--$buffered;
					}
					else
					{
						++$buffered;
						$emPos = $matchPos;
					}
				}
			}
		}
	}

	/**
	* Decode a chunk of encoded text to be used as an attribute value
	*
	* Decodes escaped literals and removes slashes and 0x1A characters
	*
	* @param  string $str      Encoded text
	* @param  bool   $unescape Whether to unescape 0x1B sequences
	* @return string           Decoded text
	*/
	protected static function decode($str, $unescape)
	{
		$str = stripslashes(str_replace("\x1A", '', $str));

		if ($unescape)
		{
			$decode = [
				"\x1B0" => '!',
				"\x1B1" => '"',
				"\x1B2" => ')',
				"\x1B3" => '*',
				"\x1B4" => '[',
				"\x1B5" => '\\',
				"\x1B6" => ']',
				"\x1B7" => '^',
				"\x1B8" => '_',
				"\x1B9" => '`',
				"\x1BA" => '~'
			];

			$str = strtr($str, $decode);
		}

		return $str;
	}

	/**
	* Overwrite part of the text with substitution characters ^Z (0x1A)
	*
	* @param  string  $text Original text
	* @param  integer $pos  Start of the range
	* @param  integer $len  Length of text to overwrite
	* @return void
	*/
	protected function overwrite(&$text, $pos, $len)
	{
		$text = substr($text, 0, $pos) . str_repeat("\x1A", $len) . substr($text, $pos + $len);
	}
}