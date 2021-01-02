<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2021 The s9e authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\FancyPants;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* @var bool Whether currrent test contains a double quote character
	*/
	protected $hasDoubleQuote;

	/**
	* @var bool Whether currrent test contains a single quote character
	*/
	protected $hasSingleQuote;

	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$this->text           = $text;
		$this->hasSingleQuote = (strpos($text, "'") !== false);
		$this->hasDoubleQuote = (strpos($text, '"') !== false);

		if (empty($this->config['disableQuotes']))
		{
			$this->parseSingleQuotes();
			$this->parseSingleQuotePairs();
			$this->parseDoubleQuotePairs();
		}
		if (empty($this->config['disableGuillemets']))
		{
			$this->parseGuillemets();
		}
		if (empty($this->config['disableMathSymbols']))
		{
			$this->parseNotEqualSign();
			$this->parseSymbolsAfterDigits();
			$this->parseFractions();
		}
		if (empty($this->config['disablePunctuation']))
		{
			$this->parseDashesAndEllipses();
		}
		if (empty($this->config['disableSymbols']))
		{
			$this->parseSymbolsInParentheses();
		}

		unset($this->text);
	}

	/**
	* Add a fancy replacement tag
	*
	* @param  integer $tagPos Position of the tag in the text
	* @param  integer $tagLen Length of text consumed by the tag
	* @param  string  $chr    Replacement character
	* @param  integer $prio   Tag's priority
	* @return \s9e\TextFormatter\Parser\Tag
	*/
	protected function addTag($tagPos, $tagLen, $chr, $prio = 0)
	{
		$tag = $this->parser->addSelfClosingTag($this->config['tagName'], $tagPos, $tagLen, $prio);
		$tag->setAttribute($this->config['attrName'], $chr);

		return $tag;
	}

	/**
	* Parse dashes and ellipses
	*
	* Does en dash –, em dash — and ellipsis …
	*
	* @return void
	*/
	protected function parseDashesAndEllipses()
	{
		if (strpos($this->text, '...') === false && strpos($this->text, '--') === false)
		{
			return;
		}

		$chrs = [
			'--'  => "\xE2\x80\x93",
			'---' => "\xE2\x80\x94",
			'...' => "\xE2\x80\xA6"
		];
		$regexp = '/---?|\\.\\.\\./S';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$this->addTag($m[1], strlen($m[0]), $chrs[$m[0]]);
		}
	}

	/**
	* Parse pairs of double quotes
	*
	* Does quote pairs “” -- must be done separately to handle nesting
	*
	* @return void
	*/
	protected function parseDoubleQuotePairs()
	{
		if ($this->hasDoubleQuote)
		{
			$this->parseQuotePairs(
				'/(?<![0-9\\pL])"[^"\\n]+"(?![0-9\\pL])/uS',
				"\xE2\x80\x9C",
				"\xE2\x80\x9D"
			);
		}
	}

	/**
	* Parse vulgar fractions
	*
	* @return void
	*/
	protected function parseFractions()
	{
		if (strpos($this->text, '/') === false)
		{
			return;
		}

		$map = [
			'1/4'  => "\xC2\xBC",
			'1/2'  => "\xC2\xBD",
			'3/4'  => "\xC2\xBE",
			'1/7'  => "\xE2\x85\x90",
			'1/9'  => "\xE2\x85\x91",
			'1/10' => "\xE2\x85\x92",
			'1/3'  => "\xE2\x85\x93",
			'2/3'  => "\xE2\x85\x94",
			'1/5'  => "\xE2\x85\x95",
			'2/5'  => "\xE2\x85\x96",
			'3/5'  => "\xE2\x85\x97",
			'4/5'  => "\xE2\x85\x98",
			'1/6'  => "\xE2\x85\x99",
			'5/6'  => "\xE2\x85\x9A",
			'1/8'  => "\xE2\x85\x9B",
			'3/8'  => "\xE2\x85\x9C",
			'5/8'  => "\xE2\x85\x9D",
			'7/8'  => "\xE2\x85\x9E",
			'0/3'  => "\xE2\x86\x89"
		];

		$regexp = '/\\b(?:0\\/3|1\\/(?:[2-9]|10)|2\\/[35]|3\\/[458]|4\\/5|5\\/[68]|7\\/8)\\b/S';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$this->addTag($m[1], strlen($m[0]), $map[$m[0]]);
		}
	}

	/**
	* Parse guillemets-style quotation marks
	*
	* @return void
	*/
	protected function parseGuillemets()
	{
		if (strpos($this->text, '<<') === false)
		{
			return;
		}

		$regexp = '/<<( ?)(?! )[^\\n<>]*?[^\\n <>]\\1>>(?!>)/';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$left  = $this->addTag($m[1],                     2, "\xC2\xAB");
			$right = $this->addTag($m[1] + strlen($m[0]) - 2, 2, "\xC2\xBB");

			$left->cascadeInvalidationTo($right);
		}
	}

	/**
	* Parse the not equal sign
	*
	* Supports != and =/=
	*
	* @return void
	*/
	protected function parseNotEqualSign()
	{
		if (strpos($this->text, '!=') === false && strpos($this->text, '=/=') === false)
		{
			return;
		}

		$regexp = '/\\b (?:!|=\\/)=(?= \\b)/';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$this->addTag($m[1] + 1, strlen($m[0]) - 1, "\xE2\x89\xA0");
		}
	}

	/**
	* Parse pairs of quotes
	*
	* @param  string $regexp     Regexp used to identify quote pairs
	* @param  string $leftQuote  Fancy replacement for left quote
	* @param  string $rightQuote Fancy replacement for right quote
	* @return void
	*/
	protected function parseQuotePairs($regexp, $leftQuote, $rightQuote)
	{
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$left  = $this->addTag($m[1], 1, $leftQuote);
			$right = $this->addTag($m[1] + strlen($m[0]) - 1, 1, $rightQuote);

			// Cascade left tag's invalidation to the right so that if we skip the left quote,
			// the right quote remains untouched
			$left->cascadeInvalidationTo($right);
		}
	}

	/**
	* Parse pairs of single quotes
	*
	* Does quote pairs ‘’ must be done separately to handle nesting
	*
	* @return void
	*/
	protected function parseSingleQuotePairs()
	{
		if ($this->hasSingleQuote)
		{
			$this->parseQuotePairs(
				"/(?<![0-9\\pL])'[^'\\n]+'(?![0-9\\pL])/uS",
				"\xE2\x80\x98",
				"\xE2\x80\x99"
			);
		}
	}

	/**
	* Parse single quotes in general
	*
	* Does apostrophes ’ after a letter or at the beginning of a word or a couple of digits
	*
	* @return void
	*/
	protected function parseSingleQuotes()
	{
		if (!$this->hasSingleQuote)
		{
			return;
		}

		$regexp = "/(?<=\\pL)'|(?<!\\S)'(?=\\pL|[0-9]{2})/uS";
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			// Give this tag a worse priority than default so that quote pairs take precedence
			$this->addTag($m[1], 1, "\xE2\x80\x99", 10);
		}
	}

	/**
	* Parse symbols found after digits
	*
	* Does symbols found after a digit:
	*  - apostrophe ’ if it's followed by an "s" as in 80's
	*  - prime ′ and double prime ″
	*  - multiply sign × if it's followed by an optional space and another digit
	*
	* @return void
	*/
	protected function parseSymbolsAfterDigits()
	{
		if (!$this->hasSingleQuote && !$this->hasDoubleQuote && strpos($this->text, 'x') === false)
		{
			return;
		}

		$map = [
			// 80's -- use an apostrophe
			"'s" => "\xE2\x80\x99",
			// 12' or 12" -- use a prime
			"'"  => "\xE2\x80\xB2",
			"' " => "\xE2\x80\xB2",
			"'x" => "\xE2\x80\xB2",
			'"'  => "\xE2\x80\xB3",
			'" ' => "\xE2\x80\xB3",
			'"x' => "\xE2\x80\xB3"
		];

		$regexp = "/[0-9](?>'s|[\"']? ?x(?= ?[0-9])|[\"'])/S";
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			// Test for a multiply sign at the end
			if (substr($m[0], -1) === 'x')
			{
				$this->addTag($m[1] + strlen($m[0]) - 1, 1, "\xC3\x97");
			}

			// Test for an apostrophe/prime right after the digit
			$str = substr($m[0], 1, 2);
			if (isset($map[$str]))
			{
				$this->addTag($m[1] + 1, 1, $map[$str]);
			}
		}
	}

	/**
	* Parse symbols found in parentheses such as (c)
	*
	* Does symbols ©, ® and ™
	*
	* @return void
	*/
	protected function parseSymbolsInParentheses()
	{
		if (strpos($this->text, '(') === false)
		{
			return;
		}

		$chrs = [
			'(c)'  => "\xC2\xA9",
			'(r)'  => "\xC2\xAE",
			'(tm)' => "\xE2\x84\xA2"
		];
		$regexp = '/\\((?>c|r|tm)\\)/i';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$this->addTag($m[1], strlen($m[0]), $chrs[strtr($m[0], 'CMRT', 'cmrt')]);
		}
	}
}