<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\FancyPants;

use s9e\TextFormatter\Plugins\ParserBase;

class Parser extends ParserBase
{
	/**
	* {@inheritdoc}
	*/
	public function parse($text, array $matches)
	{
		$attrName = $this->config['attrName'];
		$tagName  = $this->config['tagName'];

		$hasSingleQuote = (strpos($text, "'") !== false);
		$hasDoubleQuote = (strpos($text, '"') !== false);

		// Do apostrophes ’ after a letter or at the beginning of a word or a couple of digits
		if ($hasSingleQuote)
		{
			preg_match_all(
				"/(?<=\\pL)'|(?<!\\S)'(?=\\pL|[0-9]{2})/uS",
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);
			foreach ($matches[0] as $m)
			{
				$tag = $this->parser->addSelfClosingTag($tagName, $m[1], 1);
				$tag->setAttribute($attrName, "\xE2\x80\x99");

				// Give this tag a worse priority than default so that quote pairs take precedence
				$tag->setSortPriority(10);
			}
		}

		// Do symbols found after a digit:
		//  - apostrophe ’ if it's followed by an "s" as in 80's
		//  - prime ′ and double prime ″
		//  - multiply sign × if it's followed by an optional space and another digit
		if ($hasSingleQuote || $hasDoubleQuote || strpos($text, 'x') !== false)
		{
			preg_match_all(
				'/[0-9](?>\'s|["\']? ?x(?= ?[0-9])|["\'])/S',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);
			foreach ($matches[0] as $m)
			{
				// Test for a multiply sign at the end
				if (substr($m[0], -1) === 'x')
				{
					$pos = $m[1] + strlen($m[0]) - 1;
					$chr = "\xC3\x97";

					$this->parser->addSelfClosingTag($tagName, $pos, 1)->setAttribute($attrName, $chr);
				}

				// Test for a apostrophe/prime right after the digit
				$c = $m[0][1];
				if ($c === "'" || $c === '"')
				{
					$pos = 1 + $m[1];

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

					$this->parser->addSelfClosingTag($tagName, $pos, 1)->setAttribute($attrName, $chr);
				}
			}
		}

		// Do quote pairs ‘’ and “” -- must be done separately to handle nesting
		$replacements = [];
		if ($hasSingleQuote)
		{
			$replacements[] = [
				"/(?<![0-9\\pL])'[^'\\n]+'(?![0-9\\pL])/uS", "\xE2\x80\x98", "\xE2\x80\x99"
			];
		}
		if ($hasDoubleQuote)
		{
			$replacements[] = [
				'/(?<![0-9\\pL])"[^"\\n]+"(?![0-9\\pL])/uS', "\xE2\x80\x9C", "\xE2\x80\x9D"
			];
		}
		foreach ($replacements as list($regexp, $leftQuote, $rightQuote))
		{
			preg_match_all($regexp, $text, $matches, PREG_OFFSET_CAPTURE);
			foreach ($matches[0] as $m)
			{
				$left  = $this->parser->addSelfClosingTag($tagName, $m[1], 1);
				$right = $this->parser->addSelfClosingTag($tagName, $m[1] + strlen($m[0]) - 1, 1);

				$left->setAttribute($attrName, $leftQuote);
				$right->setAttribute($attrName, $rightQuote);

				// Cascade left tag's invalidation to the right so that if we skip the left quote,
				// the right quote is left untouched
				$left->cascadeInvalidationTo($right);
			}
		}

		// Do en dash –, em dash — and ellipsis …
		if (strpos($text, '...') !== false
		 || strpos($text, '--')  !== false)
		{
			preg_match_all(
				'/---?|\\.\\.\\./S',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);
			$chrs = [
				'--'  => "\xE2\x80\x93",
				'---' => "\xE2\x80\x94",
				'...' => "\xE2\x80\xA6"
			];
			foreach ($matches[0] as $m)
			{
				$pos = $m[1];
				$len = strlen($m[0]);
				$chr = $chrs[$m[0]];

				$this->parser->addSelfClosingTag($tagName, $pos, $len)->setAttribute($attrName, $chr);
			}
		}

		// Do symbols ©, ® and ™
		if (strpos($text, '(') !== false)
		{
			preg_match_all(
				'/\\((?>c|r|tm)\\)/i',
				$text,
				$matches,
				PREG_OFFSET_CAPTURE
			);
			$chrs = [
				'(c)'  => "\xC2\xA9",
				'(r)'  => "\xC2\xAE",
				'(tm)' => "\xE2\x84\xA2"
			];
			foreach ($matches[0] as $m)
			{
				$pos = $m[1];
				$len = strlen($m[0]);
				$chr = $chrs[strtr($m[0], 'CMRT', 'cmrt')];

				$this->parser->addSelfClosingTag($tagName, $pos, $len)->setAttribute($attrName, $chr);
			}
		}
	}
}