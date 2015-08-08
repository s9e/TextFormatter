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
				'<r><i>\\</i>[</r>'
			],
			[
				"a\\\nb",
				"<r>a<i>\\</i>\nb</r>",
				[],
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			],
			[
				'a\\♥b',
				'<r>a<i>\\</i>♥b</r>',
				[],
				function ($configurator, $plugin)
				{
					$plugin->escapeAll();
				}
			],
			[
				'\\*foo*bar*',
				'<r><i>\\</i>*foo<EM><s>*</s>bar<e>*</e></EM></r>',
				[],
				function ($configurator, $plugin)
				{
					$configurator->Litedown;
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