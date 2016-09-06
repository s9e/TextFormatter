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
				'⚫️',
				'<r><EMOJI seq="26ab">⚫️</EMOJI></r>'
			],
			[
				// U+263A U+FE0F
				'☺️',
				'<r><EMOJI seq="263a">☺️</EMOJI></r>'
			],
			[
				// U+263A
				'☺',
				'<t>☺</t>'
			],
			[
				// U+2615
				'☕',
				'<r><EMOJI seq="2615">☕</EMOJI></r>'
			],
			[
				// U+2615 U+FE0E
				'☕︎',
				'<t>☕︎</t>'
			],
			[
				'☺️',
				'<r><EMO seq="263a">☺️</EMO></r>',
				['tagName' => 'EMO']
			],
			[
				'☺️',
				'<r><EMOJI cp="263a">☺️</EMOJI></r>',
				['attrName' => 'cp']
			],
			[
				'☺️☺️',
				'<r><EMOJI seq="263a">☺️</EMOJI><EMOJI seq="263a">☺️</EMOJI></r>'
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
				'#️⃣1️⃣2️⃣',
				'<r><EMOJI seq="23-20e3">#️⃣</EMOJI><EMOJI seq="31-20e3">1️⃣</EMOJI><EMOJI seq="32-20e3">2️⃣</EMOJI></r>'
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
				':bouquet:',
				'<r><E>:bouquet:</E></r>',
				[],
				function ($configurator, $plugin)
				{
					$configurator->Emoticons->add(':bouquet:', '...');
				}
			],
			[
				'🇯🇵',
				'<r><EMOJI seq="1f1ef-1f1f5">&#127471;&#127477;</EMOJI></r>'
			],
			[
				':D',
				'<r><EMOJI seq="1f600">:D</EMOJI></r>',
				[],
				function ($configurator, $plugin)
				{
					$configurator->Emoji->addAlias(':D', '😀');
				}
			],
			[
				':P',
				'<t>:P</t>',
				[],
				function ($configurator, $plugin)
				{
					$configurator->Emoji->addAlias(':p', '😀');
				}
			],
			[
				// :copyright: is in gemoji, not emojione
				// :unicorn: is in emojione, not gemoji
				':copyright: :unicorn:',
				'<r><EMOJI seq="00a9">:copyright:</EMOJI> <EMOJI seq="1f984">:unicorn:</EMOJI></r>'
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
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//twemoji.maxcdn.com/2/72x72/1f600.png">'
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" src="//twemoji.maxcdn.com/2/72x72/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->omitImageSize();
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//twemoji.maxcdn.com/2/72x72/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->setImageSize(16);
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="24" height="24" src="//twemoji.maxcdn.com/2/72x72/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->setImageSize(24);
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="36" height="36" src="//twemoji.maxcdn.com/2/72x72/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->setImageSize(36);
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="72" height="72" src="//twemoji.maxcdn.com/2/72x72/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->setImageSize(72);
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="720" height="720" src="//twemoji.maxcdn.com/2/72x72/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->setImageSize(720);
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//twemoji.maxcdn.com/2/svg/1f600.svg">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->useSVG();
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//twemoji.maxcdn.com/2/72x72/1f600.png">',
				['tagName' => 'EMOTE']
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->useEmojiOne();
				}
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/png/1f600.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->useEmojiOne();
					$plugin->omitImageSize();
				}
			],
			[
				'#️⃣1️⃣2️⃣',
				'<img alt="#️⃣" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/0023-20e3.png"><img alt="1️⃣" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/0031-20e3.png"><img alt="2️⃣" class="emoji" draggable="false" width="16" height="16" src="//cdn.jsdelivr.net/emojione/assets/png/0032-20e3.png">',
				[],
				function ($configurator, $plugin)
				{
					$plugin->useEmojiOne();
				}
			],
		];
	}
}