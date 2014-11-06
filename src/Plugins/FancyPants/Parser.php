<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\FancyPants;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	protected $text;

	public function parse($text, array $matches)
	{
		$this->text = $text;

		$hasSingleQuote = (\strpos($text, "'") !== \false);
		$hasDoubleQuote = (\strpos($text, '"') !== \false);

		if ($hasSingleQuote)
			$this->parseSingleQuotes();

		if ($hasSingleQuote || $hasDoubleQuote || \strpos($text, 'x') !== \false)
			$this->parseSymbolsAfterDigits();

		if ($hasSingleQuote)
			$this->parseSingleQuotePairs();
		if ($hasDoubleQuote)
			$this->parseDoubleQuotePairs();

		if (\strpos($text, '...') !== \false || \strpos($text, '--')  !== \false)
			$this->parseDashesAndEllipses();

		if (\strpos($text, '(') !== \false)
			$this->parseSymbolsInParentheses();

		unset($this->text);
	}

	protected function parseDashesAndEllipses()
	{
		\preg_match_all(
			'/---?|\\.\\.\\./S',
			$this->text,
			$matches,
			256
		);
		$chrs = array(
			'--'  => "\xE2\x80\x93",
			'---' => "\xE2\x80\x94",
			'...' => "\xE2\x80\xA6"
		);
		foreach ($matches[0] as $m)
		{
			$pos = $m[1];
			$len = \strlen($m[0]);
			$chr = $chrs[$m[0]];

			$this->parser->addSelfClosingTag($this->config['tagName'], $pos, $len)->setAttribute($this->config['attrName'], $chr);
		}
	}

	protected function parseDoubleQuotePairs()
	{
		$this->parseQuotePairs(
			'/(?<![0-9\\pL])"[^"\\n]+"(?![0-9\\pL])/uS',
			"\xE2\x80\x9C",
			"\xE2\x80\x9D"
		);
	}

	protected function parseQuotePairs($regexp, $leftQuote, $rightQuote)
	{
		\preg_match_all($regexp, $this->text, $matches, 256);
		foreach ($matches[0] as $m)
		{
			$left  = $this->parser->addSelfClosingTag($this->config['tagName'], $m[1], 1);
			$right = $this->parser->addSelfClosingTag($this->config['tagName'], $m[1] + \strlen($m[0]) - 1, 1);

			$left->setAttribute($this->config['attrName'], $leftQuote);
			$right->setAttribute($this->config['attrName'], $rightQuote);

			$left->cascadeInvalidationTo($right);
		}
	}

	protected function parseSingleQuotePairs()
	{
		$this->parseQuotePairs(
			"/(?<![0-9\\pL])'[^'\\n]+'(?![0-9\\pL])/uS",
			"\xE2\x80\x98",
			"\xE2\x80\x99"
		);
	}

	protected function parseSingleQuotes()
	{
		\preg_match_all(
			"/(?<=\\pL)'|(?<!\\S)'(?=\\pL|[0-9]{2})/uS",
			$this->text,
			$matches,
			256
		);

		foreach ($matches[0] as $m)
		{
			$tag = $this->parser->addSelfClosingTag($this->config['tagName'], $m[1], 1);
			$tag->setAttribute($this->config['attrName'], "\xE2\x80\x99");

			$tag->setSortPriority(10);
		}
	}

	protected function parseSymbolsAfterDigits()
	{
		\preg_match_all(
			'/[0-9](?>\'s|["\']? ?x(?= ?[0-9])|["\'])/S',
			$this->text,
			$matches,
			256
		);

		foreach ($matches[0] as $m)
		{
			if (\substr($m[0], -1) === 'x')
			{
				$pos = $m[1] + \strlen($m[0]) - 1;
				$chr = "\xC3\x97";

				$this->parser->addSelfClosingTag($this->config['tagName'], $pos, 1)->setAttribute($this->config['attrName'], $chr);
			}

			$c = $m[0][1];
			if ($c === "'" || $c === '"')
			{
				$pos = 1 + $m[1];

				if (\substr($m[0], 1, 2) === "'s")
					$chr = "\xE2\x80\x99";
				else
					$chr = ($c === "'") ? "\xE2\x80\xB2" : "\xE2\x80\xB3";

				$this->parser->addSelfClosingTag($this->config['tagName'], $pos, 1)->setAttribute($this->config['attrName'], $chr);
			}
		}
	}

	protected function parseSymbolsInParentheses()
	{
		\preg_match_all(
			'/\\((?>c|r|tm)\\)/i',
			$this->text,
			$matches,
			256
		);
		$chrs = array(
			'(c)'  => "\xC2\xA9",
			'(r)'  => "\xC2\xAE",
			'(tm)' => "\xE2\x84\xA2"
		);
		foreach ($matches[0] as $m)
		{
			$pos = $m[1];
			$len = \strlen($m[0]);
			$chr = $chrs[\strtr($m[0], 'CMRT', 'cmrt')];

			$this->parser->addSelfClosingTag($this->config['tagName'], $pos, $len)->setAttribute($this->config['attrName'], $chr);
		}
	}
}