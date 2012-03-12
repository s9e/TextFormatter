<?php

/**
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2012 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins;

use s9e\TextFormatter\Parser,
    s9e\TextFormatter\PluginParser;

class WittyPantsParser extends PluginParser
{
	public function getTags($text, array $matches)
	{
		$tags = array();

		$tagName      = $this->config['tagName'];
		$attrName     = $this->config['attrName'];
		$replacements = $this->config['replacements'];

		foreach ($matches['singletons'] as $m)
		{
			$tags[] = array(
				'pos'   => $m[0][1],
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $tagName,
				'len'   => strlen($m[0][0]),
				'attrs' => array(
					$attrName => $replacements['singletons'][$m[0][0]]
				)
			);
		}

		foreach (array_merge($matches['quotationSingle'], $matches['quotationDouble']) as $m)
		{
			// left character
			$tags[] = array(
				'pos'   => $m[0][1],
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $tagName,
				'len'   => 1,
				'attrs' => array(
					$attrName => $replacements['quotation'][$m[0][0][0]][0]
				)
			);

			// right character
			$tags[] = array(
				'pos'   => $m[0][1] + strlen($m[0][0]) - 1,
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $tagName,
				'len'   => 1,
				'attrs' => array(
					$attrName => $replacements['quotation'][$m[0][0][0]][1]
				),
				'requires' => array(count($tags) - 1)
			);
		}

		foreach ($matches['symbols'] as $m)
		{
			$tags[] = array(
				'pos'   => $m[0][1],
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $tagName,
				'len'   => strlen($m[0][0]),
				'attrs' => array(
					$attrName => $replacements['symbols'][strtr($m[0][0], "CTMR", 'ctmr')]
				)
			);
		}

		foreach ($matches['apostrophe'] as $m)
		{
			$tags[] = array(
				'pos'   => $m[0][1],
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $tagName,
				'len'   => 1,
				'attrs' => array(
					$attrName => $replacements['apostrophe']
				)
			);
		}

		/**
		* We do "primes" after "apostrophe" so that the character in "80's" gets replaced by an
		* apostrophe rather than a prime
		*/
		foreach ($matches['primes'] as $m)
		{
			$tags[] = array(
				'pos'   => 1 + $m[0][1],
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $tagName,
				'len'   => 1,
				'attrs' => array(
					$attrName => $replacements['primes'][$m[0][0][1]]
				)
			);
		}

		foreach ($matches['multiply'] as $m)
		{
			$tags[] = array(
				'pos'   => $m[1][1],
				'type'  => Parser::SELF_CLOSING_TAG,
				'name'  => $tagName,
				'len'   => 1,
				'attrs' => array(
					$attrName => $replacements['multiply']
				)
			);
		}

		return $tags;
	}
}