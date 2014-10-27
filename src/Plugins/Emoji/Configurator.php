<?php

/*
* @package   s9e\TextFormatter
* @copyright Copyright (c) 2010-2014 The s9e Authors
* @license   http://www.opensource.org/licenses/mit-license.php The MIT License
*/
namespace s9e\TextFormatter\Plugins\Emoji;

use s9e\TextFormatter\Configurator\Items\Variant;
use s9e\TextFormatter\Configurator\JavaScript\RegExp;
use s9e\TextFormatter\Plugins\ConfiguratorBase;

class Configurator extends ConfiguratorBase
{
	/*
	* @var string Name of the attribute used by this plugin
	*/
	protected $attrName = 'seq';

	/*
	* @var string Name of the tag used by this plugin
	*/
	protected $tagName = 'E1';

	/*
	* Plugin's setup
	*
	* Will create the tag used by this plugin
	*/
	protected function setUp()
	{
		if (isset($this->configurator->tags[$this->tagName]))
			return;

		$tag = $this->configurator->tags->add($this->tagName);
		$tag->attributes->add($this->attrName)->filterChain->append(
			$this->configurator->attributeFilters['#identifier']
		);
		$tag->template = '<img alt="{.}" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/{@seq}.png"/>';
	}

	/*
	* {@inheritdoc}
	*/
	public function asConfig()
	{
		$phpRegexp = '(';
		$jsRegexp  = '';

		// Start with a lookahead assertion for performance
		//
		// NOTE: PCRE does not really require it if the S flag is set, but it doesn't seem to
		//       negatively impact performance
		$phpRegexp .= '(?=[#0-9:\\xC2\\xE2\\xE3\\xF0])';

		// Start the main alternation
		$phpRegexp .= '(?>';
		$jsRegexp  .= '(?:';

		// Shortcodes
		$phpRegexp .= ':[-+_a-z0-9]+(?=:)';
		$jsRegexp  .= ':[-+_a-z0-9]+(?=:)';

		// Start the Emoji alternation
		$phpRegexp .= '|(?>';
		$jsRegexp  .= '|(?:';

		// Keypad emoji: starts with [#0-9], optional U+FE0F, ends with U+20E3
		$phpRegexp .= '[#0-9](?>\\xEF\\xB8\\x8F)?\\xE2\\x83\\xA3';
		$jsRegexp  .= '[#0-9]\\uFE0F?\\u20E3';

		// (c) and (r). We also start a character class in JS
		$phpRegexp .= '|\\xC2[\\xA9\\xAE]';
		$jsRegexp  .= '|[\\u00A9\\u00AE';

		// 0xE2XXXX block: U+203C..U+2B55. We try to avoid common symbols such as U+2018..U+201D
		$phpRegexp .= '|\\xE2(?>\\x80\\xBC|[\\x81-\\xAD].)';
		$jsRegexp  .= '\\u203C\\u2049\u2122-\\u2B55';

		// 0xE3XXXX block: U+3030, U+303D, U+3297, U+3299. Also the end of the JS character class
		$phpRegexp .= '|\\xE3(?>\\x80[\\xB0\\xBD]|\\x8A[\\x97\\x99])';
		$jsRegexp  .= '\\u3030\\u303D\\u3297\\u3299]';

		// Start the 0xF09FXXXX block
		$phpRegexp .= '|\\xF0\\x9F(?>';
		$jsRegexp  .= '|\\uD83C';

		// Subblock: 0x80XX..0x86XX
		//
		//    0xF09F8084..0xF09F869A
		//       U+1F004..U+1F19A
		// U+D83C U+DC04..U+D83C U+DD9A
		$phpRegexp .= '[\\x80-\\x86].';
		$jsRegexp  .= '[\\uDC04-\\uDD9A';

		// Subblock: 0x87XX (flag pairs)
		//
		//    0xF09F87A8..0xF09F87BA
		//       U+1F1E8..U+1F1FA
		// U+D83C U+DDE8..U+D83C U+DDFA
		$phpRegexp .= '|\\x87.\\xF0\\x9F\\x87.';
		$jsRegexp  .= '\\uDDE8-\\uDDFA';

		// Subblock: 0x88XX..0x9BXX
		//
		//    0xF09F8881..0xF09F9B85
		//       U+1F201..U+1F3FF
		// U+D83C U+DE01..U+D83C U+DFFF
		//       U+1F400..U+1F6C5
		// U+D83D U+DC00..U+D83D U+DEC5
		$phpRegexp .= '|[\\x88-\\x9B].';
		$jsRegexp  .= '\\uDE01-\\uDFFF]|\\uD83D[\\uDC00-\\uDEC5]';

		// Close the 0xF09FXXXX block
		$phpRegexp .= ')';

		// Close the Emoji alternation, optionally followed by U+FE0F
		$phpRegexp .= ')(?>\\xEF\\xB8\\x8F)?';
		$jsRegexp  .= ')\uFE0F?';

		// Close the main alternation
		$phpRegexp .= ')';
		$jsRegexp  .= ')';

		// End the PHP regexp with the S modifier
		$phpRegexp .= ')S';

		// Create a Variant to hold both regexps
		$regexp = new Variant($phpRegexp);
		$regexp->set('JS', new RegExp($jsRegexp, 'g'));

		return array(
			'attrName' => $this->attrName,
			'regexp'   => $regexp,
			'tagName'  => $this->tagName
		);
	}
}