<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2013 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\MarkdownLite;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		// Encode escaped literals that have a special meaning otherwise, so that we don't have to
		// take them into account in regexps
		if (strpos($text, '\\') !== false && preg_match('/\\\\[!)*[\\\\\\]^_`~]/', $text))
		{
			$text = strtr(
				$text,
				[
					'\\!'  => "\x1B0",
					'\\)'  => "\x1B1",
					'\\*'  => "\x1B2",
					'\\['  => "\x1B3",
					'\\\\' => "\x1B4",
					'\\]'  => "\x1B5",
					'\\^'  => "\x1B6",
					'\\_'  => "\x1B7",
					'\\`'  => "\x1B8",
					'\\~'  => "\x1B9"
				]
			);

			$decode = [__CLASS__, 'decode'];
		}
		else
		{
			$decode = 'stripslashes';
		}

		$lines = explode("\n", $text);
		foreach ($lines as $line)
		{
			$spn = strspn($line, ' -+*#>0123456789.');

			if (!$spn)
			{
				continue;
			}

			// Blockquote: ">" or "> "
			// List item:  "* " preceded by any number of spaces
			// List item:  "- " preceded by any number of spaces
			// List item:  "+ " preceded by any number of spaces
			// List item:  at least one digit followed by ". "
			// HR:         "* * *" or "- - -" or "***" or "---"
		}

		// Images
		if (strpos($text, '![') !== false)
		{
			preg_match_all(
				'/!\\[([^\\]]++)] ?\\(([^ ")]++)(?> "([^)]*)")?\\)/',
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
				$startTag->setAttribute('alt', $decode($m[1][0]));
				$startTag->setAttribute('src', $decode($m[2][0]));

				if (isset($m[3]))
				{
					$startTag->setAttribute('title', $decode($m[3][0]));
				}

				// Overwrite the markup
				self::overwrite($text, $matchPos, $matchLen);
			}
		}

		// Inline links
		if (strpos($text, '[') !== false)
		{
			preg_match_all(
				'/\\[([^\\]]++)] ?\\(([^)]++)\\)/',
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
				             ->setAttribute('url', $decode($m[2][0]));
			}

			// Overwrite the markup
			self::overwrite($text, $matchPos, $matchLen);
		}
	}

	/**
	* Decode a chunk of encoded text to be used as an attribute value
	*
	* Decodes escaped literals and removes slashes
	*
	* @param  string $str Encoded text
	* @return string      Decoded text
	*/
	protected static function decode($str)
	{
		$decode = [
			"\x1B0" => '!',
			"\x1B1" => ')',
			"\x1B2" => '*',
			"\x1B3" => '[',
			"\x1B4" => '\\',
			"\x1B5" => ']',
			"\x1B6" => '^',
			"\x1B7" => '_',
			"\x1B8" => '`',
			"\x1B9" => '~'
		];

		return strtr(stripslashes($str), $decode);
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