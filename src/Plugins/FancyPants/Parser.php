<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2015 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\FancyPants;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* @var string Text being parsed
	*/
	protected $text;

	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$this->text = $text;

		$hasSingleQuote = (strpos($text, "'") !== false);
		$hasDoubleQuote = (strpos($text, '"') !== false);

		// Do apostrophes ’ after a letter or at the beginning of a word or a couple of digits
		if ($hasSingleQuote)
		{
			$this->parseSingleQuotes();
		}

		// Do symbols found after a digit:
		//  - apostrophe ’ if it's followed by an "s" as in 80's
		//  - prime ′ and double prime ″
		//  - multiply sign × if it's followed by an optional space and another digit
		if ($hasSingleQuote || $hasDoubleQuote || strpos($text, 'x') !== false)
		{
			$this->parseSymbolsAfterDigits();
		}

		// Do quote pairs ‘’ and “” -- must be done separately to handle nesting
		if ($hasSingleQuote)
		{
			$this->parseSingleQuotePairs();
		}
		if ($hasDoubleQuote)
		{
			$this->parseDoubleQuotePairs();
		}

		// Do en dash –, em dash — and ellipsis …
		if (strpos($text, '...') !== false || strpos($text, '--')  !== false)
		{
			$this->parseDashesAndEllipses();
		}

		// Do symbols ©, ® and ™
		if (strpos($text, '(') !== false)
		{
			$this->parseSymbolsInParentheses();
		}

		unset($this->text);
	}

	/**
	* Add a fancy replacement tag
	*
	* @param  integer $tagPos
	* @param  integer $tagLen
	* @param  string  $chr
	* @return \s9e\TextFormatter\Parser\Tag
	*/
	protected function addTag($tagPos, $tagLen, $chr)
	{
		$tag = $this->parser->addSelfClosingTag($this->config['tagName'], $tagPos, $tagLen);
		$tag->setAttribute($this->config['attrName'], $chr);

		return $tag;
	}

	/**
	* Parse dashes and ellipses
	*
	* @return void
	*/
	protected function parseDashesAndEllipses()
	{
		$chrs = array(
			'--'  => "\xE2\x80\x93",
			'---' => "\xE2\x80\x94",
			'...' => "\xE2\x80\xA6"
		);
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
	* @return void
	*/
	protected function parseDoubleQuotePairs()
	{
		$this->parseQuotePairs(
			'/(?<![0-9\\pL])"[^"\\n]+"(?![0-9\\pL])/uS',
			"\xE2\x80\x9C",
			"\xE2\x80\x9D"
		);
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
			// the right quote is left untouched
			$left->cascadeInvalidationTo($right);
		}
	}

	/**
	* Parse pairs of single quotes
	*
	* @return void
	*/
	protected function parseSingleQuotePairs()
	{
		$this->parseQuotePairs(
			"/(?<![0-9\\pL])'[^'\\n]+'(?![0-9\\pL])/uS",
			"\xE2\x80\x98",
			"\xE2\x80\x99"
		);
	}

	/**
	* Parse single quotes in general
	*
	* @return void
	*/
	protected function parseSingleQuotes()
	{
		$regexp = "/(?<=\\pL)'|(?<!\\S)'(?=\\pL|[0-9]{2})/uS";
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$tag = $this->addTag($m[1], 1, "\xE2\x80\x99");

			// Give this tag a worse priority than default so that quote pairs take precedence
			$tag->setSortPriority(10);
		}
	}

	/**
	* Parse symbols found after digits
	*
	* @return void
	*/
	protected function parseSymbolsAfterDigits()
	{
		$regexp = '/[0-9](?>\'s|["\']? ?x(?= ?[0-9])|["\'])/S';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			// Test for a multiply sign at the end
			if (substr($m[0], -1) === 'x')
			{
				$this->addTag($m[1] + strlen($m[0]) - 1, 1, "\xC3\x97");
			}

			// Test for a apostrophe/prime right after the digit
			$c = $m[0][1];
			if ($c === "'" || $c === '"')
			{
				if (substr($m[0], 1, 2) === "'s")
				{
					// 80's -- use an apostrophe
					$chr = "\xE2\x80\x99";
				}
				else
				{
					// 12' or 12" -- use a prime
					$chr = ($c === "'") ? "\xE2\x80\xB2" : "\xE2\x80\xB3";
				}

				$this->addTag($m[1] + 1, 1, $chr);
			}
		}
	}

	/**
	* Parse symbols found in parentheses such as (c)
	*
	* @return void
	*/
	protected function parseSymbolsInParentheses()
	{
		$chrs = array(
			'(c)'  => "\xC2\xA9",
			'(r)'  => "\xC2\xAE",
			'(tm)' => "\xE2\x84\xA2"
		);
		$regexp = '/\\((?>c|r|tm)\\)/i';
		preg_match_all($regexp, $this->text, $matches, PREG_OFFSET_CAPTURE);
		foreach ($matches[0] as $m)
		{
			$this->addTag($m[1], strlen($m[0]), $chrs[strtr($m[0], 'CMRT', 'cmrt')]);
		}
	}
}