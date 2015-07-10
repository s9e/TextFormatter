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
				'<r><EMOJI seq="263a">☺</EMOJI></r>'
			],
			[
				'☺',
				'<r><EMO seq="263a">☺</EMO></r>',
				['tagName' => 'EMO']
			],
			[
				'☺',
				'<r><EMOJI cp="263a">☺</EMOJI></r>',
				['attrName' => 'cp']
			],
			[
				'☺☺',
				'<r><EMOJI seq="263a">☺</EMOJI><EMOJI seq="263a">☺</EMOJI></r>'
			],
			[
				'😀',
				'<r><EMOJI seq="1f600">&#128512;</EMOJI></r>'
			],
			[
				'▬',
				'<t>▬</t>'
			],
			[
				'#⃣1⃣2⃣',
				'<r><EMOJI seq="23-20e3">#⃣</EMOJI><EMOJI seq="31-20e3">1⃣</EMOJI><EMOJI seq="32-20e3">2⃣</EMOJI></r>'
			],
			[
				':bouquet:',
				'<r><EMOJI seq="1f490">:bouquet:</EMOJI></r>'
			],
			[
				':xyz:',
				'<t>:xyz:</t>'
			],
			[
				':xyz:bouquet:',
				'<r>:xyz<EMOJI seq="1f490">:bouquet:</EMOJI></r>'
			],
			[
				file_get_contents(__DIR__ . '/all.txt'),
				file_get_contents(__DIR__ . '/all.xml'),
				[],
				function ($configurator, $plugin)
				{
					$plugin->setRegexpLimit(10000);
					$plugin->getTag()->tagLimit = 10000;
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/36x36/263a.png">'
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/16x16/263a.png">',
				['imageSize' => 16]
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/36x36/263a.png">',
				['imageSize' => 24]
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/36x36/263a.png">',
				['imageSize' => 36]
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/72x72/263a.png">',
				['imageSize' => 72]
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/72x72/263a.png">',
				['imageSize' => 720]
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/svg/263a.svg">',
				['imageType' => 'svg']
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//twemoji.maxcdn.com/36x36/263a.png">',
				['tagName' => 'EMOTE']
			],
			[
				'☺',
				'<img alt="☺" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/png/263A.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->useEmojiOne();
				}
			],
			[
				'#⃣1⃣2⃣',
				'<img alt="#⃣" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/png/0023-20E3.png"><img alt="1⃣" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/png/0031-20E3.png"><img alt="2⃣" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/png/0032-20E3.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->useEmojiOne();
				}
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