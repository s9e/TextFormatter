<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2023 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Litedown\Parser;

class ParsedText
{
	/**
	* @var bool Whether to decode HTML entities when decoding text
	*/
	public $decodeHtmlEntities = false;

	/**
	* @var bool Whether text contains escape characters
	*/
	protected $hasEscapedChars = false;

	/**
	* @var bool Whether text contains link references
	*/
	public $hasReferences = false;

	/**
	* @var array Array of [label => link info]
	*/
	public $linkReferences = [];

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* @param string $text Original text
	*/
	public function __construct($text)
	{
		if (strpos($text, '\\') !== false && preg_match('/\\\\[!"\'()*<>[\\\\\\]^_`~]/', $text))
		{
			$this->hasEscapedChars = true;

			// Encode escaped literals that have a special meaning otherwise, so that we don't have
			// to take them into account in regexps
			$text = strtr(
				$text,
				[
					'\\!' => "\x1B0", '\\"'  => "\x1B1", "\\'" => "\x1B2", '\\(' => "\x1B3",
					'\\)' => "\x1B4", '\\*'  => "\x1B5", '\\<' => "\x1B6", '\\>' => "\x1B7",
					'\\[' => "\x1B8", '\\\\' => "\x1B9", '\\]' => "\x1BA", '\\^' => "\x1BB",
					'\\_' => "\x1BC", '\\`'  => "\x1BD", '\\~' => "\x1BE"
				]
			);
		}

		// We append a couple of lines and a non-whitespace character at the end of the text in
		// order to trigger the closure of all open blocks such as quotes and lists
		$this->text = $text . "\n\n\x17";
	}

	/**
	* @return string
	*/
	public function __toString()
	{
		return $this->text;
	}

	/**
	* Return the character at given position
	*
	* @param  integer $pos
	* @return string
	*/
	public function charAt($pos)
	{
		return $this->text[$pos];
	}

	/**
	* Decode a chunk of encoded text to be used as an attribute value
	*
	* Decodes escaped literals and removes slashes and 0x1A characters
	*
	* @param  string $str Encoded text
	* @return string      Decoded text
	*/
	public function decode($str)
	{
		if ($this->decodeHtmlEntities && strpos($str, '&') !== false)
		{
			$str = html_entity_decode($str, ENT_QUOTES, 'UTF-8');
		}
		$str = str_replace("\x1A", '', $str);

		if ($this->hasEscapedChars)
		{
			$str = strtr(
				$str,
				[
					"\x1B0" => '!', "\x1B1" => '"',  "\x1B2" => "'", "\x1B3" => '(',
					"\x1B4" => ')', "\x1B5" => '*',  "\x1B6" => '<', "\x1B7" => '>',
					"\x1B8" => '[', "\x1B9" => '\\', "\x1BA" => ']', "\x1BB" => '^',
					"\x1BC" => '_', "\x1BD" => '`',  "\x1BE" => '~'
				]
			);
		}

		return $str;
	}

	/**
	* Find the first occurence of given substring starting at given position
	*
	* @param  string       $str
	* @param  integer      $pos
	* @return bool|integer
	*/
	public function indexOf($str, $pos = 0)
	{
		return strpos($this->text, $str, $pos);
	}

	/**
	* Test whether given position is preceded by whitespace
	*
	* @param  integer $pos
	* @return bool
	*/
	public function isAfterWhitespace($pos)
	{
		return ($pos > 0 && $this->isWhitespace($this->text[$pos - 1]));
	}

	/**
	* Test whether given character is alphanumeric
	*
	* @param  string $chr
	* @return bool
	*/
	public function isAlnum($chr)
	{
		return (strpos(' abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789', $chr) > 0);
	}

	/**
	* Test whether given position is followed by whitespace
	*
	* @param  integer $pos
	* @return bool
	*/
	public function isBeforeWhitespace($pos)
	{
		return $this->isWhitespace($this->text[$pos + 1]);
	}

	/**
	* Test whether a length of text is surrounded by alphanumeric characters
	*
	* @param  integer $pos Start of the text
	* @param  integer $len Length of the text
	* @return bool
	*/
	public function isSurroundedByAlnum($pos, $len)
	{
		return ($pos > 0 && $this->isAlnum($this->text[$pos - 1]) && $this->isAlnum($this->text[$pos + $len]));
	}

	/**
	* Test whether given character is an ASCII whitespace character
	*
	* NOTE: newlines are normalized to LF before parsing so we don't have to check for CR
	*
	* @param  string $chr
	* @return bool
	*/
	public function isWhitespace($chr)
	{
		return (strpos(" \n\t", $chr) !== false);
	}

	/**
	* Mark the boundary of a block in the original text
	*
	* @param  integer $pos
	* @return void
	*/
	public function markBoundary($pos)
	{
		$this->text[$pos] = "\x17";
	}

	/**
	* Overwrite part of the text with substitution characters ^Z (0x1A)
	*
	* @param  integer $pos Start of the range
	* @param  integer $len Length of text to overwrite
	* @return void
	*/
	public function overwrite($pos, $len)
	{
		if ($len > 0)
		{
			$this->text = substr($this->text, 0, $pos) . str_repeat("\x1A", $len) . substr($this->text, $pos + $len);
		}
	}
}