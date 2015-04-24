<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoji;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Emoji\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoji\Parser
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
				'☺',
				'<r><E1 seq="263A">☺</E1></r>'
			],
			[
				'☺',
				'<r><EMOJI seq="263A">☺</EMOJI></r>',
				['tagName' => 'EMOJI']
			],
			[
				'☺',
				'<r><E1 cp="263A">☺</E1></r>',
				['attrName' => 'cp']
			],
			[
				':bouquet:',
				'<r><E1 seq="1F490">:bouquet:</E1></r>'
			],
			[
				':xyz:',
				'<t>:xyz:</t>'
			],
			[
				':xyz:bouquet:',
				'<r>:xyz<E1 seq="1F490">:bouquet:</E1></r>'
			],
			[
				'☺☺',
				'<r><E1 seq="263A">☺</E1><E1 seq="263A">☺</E1></r>'
			],
			[
				'😀',
				'<r><E1 seq="1F600">&#128512;</E1></r>'
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'☺',
				'<img alt="☺" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/263A.png">'
			],
			[
				'☺',
				'<img alt="☺" class="emojione" src="//cdn.jsdelivr.net/emojione/assets/png/263A.png">',
				['tagName' => 'EMOTE']
			],
			[
				file_get_contents(__DIR__ . '/all.txt'),
				file_get_contents(__DIR__ . '/all.html'),
				[],
				function ($configurator, $plugin)
				{
					$plugin->setRegexpLimit(10000);
					$plugin->getTag()->tagLimit = 10000;
				}
			],
		];
	}
}