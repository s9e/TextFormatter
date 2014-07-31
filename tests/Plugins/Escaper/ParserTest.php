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
				'<r><ESC><s>\\</s>[</ESC></r>'
			],
			[
				'\\[',
				'<r><FOO><s>\\</s>[</FOO></r>',
				['tagName' => 'FOO']
			],
			[
				"a\\\nb",
				"<r>a<ESC><s>\\</s>\n</ESC>b</r>",
				[],
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			],
			[
				'a\\♥b',
				'<r>a<ESC><s>\\</s>♥</ESC>b</r>',
				[],
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
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
				"a\nb",
				[],
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			],
			[
				'a\\♥b',
				'a♥b',
				[],
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			],
		];
	}
}