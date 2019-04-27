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
				"\xE2\x9A\xAB",
				"<r><EMOJI seq=\"26ab\" tseq=\"26ab\">\xE2\x9A\xAB</EMOJI></r>"
			],
			[
				"\xE2\x9A\xAB\xEF\xB8\x8F",
				"<r><EMOJI seq=\"26ab\" tseq=\"26ab\">\xE2\x9A\xAB\xEF\xB8\x8F</EMOJI></r>"
			],
			[
				// U+263A U+FE0F
				'☺️',
				'<r><EMOJI seq="263a" tseq="263a">☺️</EMOJI></r>'
			],
			[
				// U+263A
				'☺',
				'<t>☺</t>'
			],
			[
				// U+2615
				'☕',
				'<r><EMOJI seq="2615" tseq="2615">☕</EMOJI></r>'
			],
			[
				// U+2615 U+FE0E
				'☕︎',
				'<t>☕︎</t>'
			],
			[
				'☺️',
				'<r><EMO seq="263a" tseq="263a">☺️</EMO></r>',
				['tagName' => 'EMO']
			],
			[
				'☺️☺️',
				'<r><EMOJI seq="263a" tseq="263a">☺️</EMOJI><EMOJI seq="263a" tseq="263a">☺️</EMOJI></r>'
			],
			[
				'😀',
				'<r><EMOJI seq="1f600" tseq="1f600">&#128512;</EMOJI></r>'
			],
			[
				'▬',
				'<t>▬</t>'
			],
			[
				'#️⃣1️⃣2️⃣',
				'<r><EMOJI seq="0023-20e3" tseq="23-fe0f-20e3">#️⃣</EMOJI><EMOJI seq="0031-20e3" tseq="31-fe0f-20e3">1️⃣</EMOJI><EMOJI seq="0032-20e3" tseq="32-fe0f-20e3">2️⃣</EMOJI></r>'
			],
			[
				':bouquet:',
				'<r><EMOJI seq="1f490" tseq="1f490">:bouquet:</EMOJI></r>'
			],
			[
				':xyz:',
				'<t>:xyz:</t>'
			],
			[
				':xyz:bouquet:',
				'<r>:xyz<EMOJI seq="1f490" tseq="1f490">:bouquet:</EMOJI></r>'
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
				// :cooking: is set as an alias to :egg: in gemoji
				':cooking:',
				'<r><EMOJI seq="1f373" tseq="1f373">:cooking:</EMOJI></r>'
			],
			[
				'🇯🇵',
				'<r><EMOJI seq="1f1ef-1f1f5" tseq="1f1ef-1f1f5">&#127471;&#127477;</EMOJI></r>'
			],
			[
				'XD',
				'<r><EMOJI seq="1f606" tseq="1f606">XD</EMOJI></r>',
				[],
				function ($configurator, $plugin)
				{
					$configurator->Emoji->addAlias('XD', '😆');
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
				'<r><EMOJI seq="00a9" tseq="a9">:copyright:</EMOJI> <EMOJI seq="1f984" tseq="1f984">:unicorn:</EMOJI></r>'
			],
			[
				'👩‍❤️‍👨',
				'<r><EMOJI seq="1f469-2764-1f468" tseq="1f469-200d-2764-fe0f-200d-1f468">&#128105;‍❤️‍&#128104;</EMOJI></r>'
			],
			[
				':00a9: :1f1ef-1f1f5: :1f468-200d-2764-fe0f-200d-1f468:',
				'<r><EMOJI seq="00a9" tseq="a9">:00a9:</EMOJI> <EMOJI seq="1f1ef-1f1f5" tseq="1f1ef-1f1f5">:1f1ef-1f1f5:</EMOJI> <EMOJI seq="1f468-2764-1f468" tseq="1f468-200d-2764-fe0f-200d-1f468">:1f468-200d-2764-fe0f-200d-1f468:</EMOJI></r>'
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
				'<img alt="😀" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.5/png/64/1f600.png">'
			],
			[
				'😀',
				'<img alt="😀" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.5/png/64/1f600.png">',
				['tagName' => 'EMOTE']
			],
			[
				':copyright::registered:#️⃣',
				'<img alt=":copyright:" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.5/png/64/00a9.png"><img alt=":registered:" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.5/png/64/00ae.png"><img alt="#️⃣" class="emoji" draggable="false" src="//cdn.jsdelivr.net/emojione/assets/4.5/png/64/0023-20e3.png">'
			],
		];
	}
}