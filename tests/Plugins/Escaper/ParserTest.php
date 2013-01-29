<?php

namespace s9e\TextFormatter\Tests\Plugins\Escaper;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Escaper\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Escaper\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return [
			[
				'\\[',
				'<rt><ESC>\\[</ESC></rt>'
			],
			[
				'\\[',
				'<rt><FOO>\\[</FOO></rt>',
				['tagName' => 'FOO']
			],
			[
				"a\\\nb",
				"<rt>a<ESC>\\\n</ESC>b</rt>"
			],
			[
				'a\\♥b',
				'<rt>a<ESC>\\♥</ESC>b</rt>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'\\[',
				'['
			],
			[
				'\\[',
				'[',
				['tagName' => 'FOO']
			],
			[
				"a\\\nb",
				"a\nb"
			],
			[
				'a\\♥b',
				'a♥b'
			],
		];
	}
}