<?php

namespace s9e\TextFormatter\Tests\Plugins\Escaper;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Escaper\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavascriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Escaper\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavascriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				'\\[',
				'<rt><ESC>\\[</ESC></rt>'
			),
			array(
				'\\[',
				'<rt><FOO>\\[</FOO></rt>',
				array('tagName' => 'FOO')
			),
			array(
				"a\\\nb",
				"<rt>a<ESC>\\\n</ESC>b</rt>"
			),
			array(
				'a\\♥b',
				'<rt>a<ESC>\\♥</ESC>b</rt>'
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				'\\[',
				'['
			),
			array(
				'\\[',
				'[',
				array('tagName' => 'FOO')
			),
			array(
				"a\\\nb",
				"a\nb"
			),
			array(
				'a\\♥b',
				'a♥b'
			),
		);
	}
}