<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoaudio;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Autoaudio\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer\AbstractParser
* @covers s9e\TextFormatter\Plugins\Autoaudio\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
	use RenderingTestsRunner;

	public static function getParsingTests()
	{
		return [
			[
				'.. http://example.org/audio.mp3 ..',
				'<r>.. <AUDIO filename="audio.mp3" src="http://example.org/audio.mp3">http://example.org/audio.mp3</AUDIO> ..</r>'
			],
			[
				'http://example.org/audio.mp3',
				'<r><AUDIO filename="audio.mp3" src="http://example.org/audio.mp3">http://example.org/audio.mp3</AUDIO></r>'
			],
			[
				'http://example.org/audio.wav',
				'<r><AUDIO filename="audio.wav" src="http://example.org/audio.wav">http://example.org/audio.wav</AUDIO></r>'
			],
			[
				'http://example.org/audio.aac',
				'<r><AUDIO filename="audio.aac" src="http://example.org/audio.aac">http://example.org/audio.aac</AUDIO></r>'
			],
			[
				'http://example.org/audio.flac',
				'<r><AUDIO filename="audio.flac" src="http://example.org/audio.flac">http://example.org/audio.flac</AUDIO></r>'
			],
			[
				'http://example.org/audio.m4a',
				'<r><AUDIO filename="audio.m4a" src="http://example.org/audio.m4a">http://example.org/audio.m4a</AUDIO></r>'
			],
			[
				'http://example.org/audio.wave',
				'<r><AUDIO filename="audio.wave" src="http://example.org/audio.wave">http://example.org/audio.wave</AUDIO></r>'
			],
			[
				'.. HTTP://EXAMPLE.ORG/AUDIO.MP3 ..',
				'<r>.. <AUDIO filename="AUDIO.MP3" src="http://EXAMPLE.ORG/AUDIO.MP3">HTTP://EXAMPLE.ORG/AUDIO.MP3</AUDIO> ..</r>'
			],
			[
				'.. http://user:pass@example.org/audio.mp3 ..',
				'<t>.. http://user:pass@example.org/audio.mp3 ..</t>'
			],
			[
				'.. http://example.org/my%20song%20(1).mp3 ..',
				'<r>.. <AUDIO filename="my song (1).mp3" src="http://example.org/my%20song%20%281%29.mp3">http://example.org/my%20song%20(1).mp3</AUDIO> ..</r>'
			],
			[
				'http://example.org/audio.mp4',
				'<t>http://example.org/audio.mp4</t>'
			],
			[
				'http://example.org/audio.mp3',
				'<r><AUDIO filename="audio.mp3" src="http://example.org/audio.mp3"><URL url="http://example.org/audio.mp3">http://example.org/audio.mp3</URL></AUDIO></r>',
				[],
				function ($configurator)
				{
					$configurator->Autolink;
				}
			],
			[
				'https://recitals.pianoworld.com/recital_files/Recital_65/11.%20navindra%20Navindra%20Umanee%20-%20Bluebird.mp3',
				'<r><AUDIO filename="11. navindra Navindra Umanee - Bluebird.mp3" src="https://recitals.pianoworld.com/recital_files/Recital_65/11.%20navindra%20Navindra%20Umanee%20-%20Bluebird.mp3">https://recitals.pianoworld.com/recital_files/Recital_65/11.%20navindra%20Navindra%20Umanee%20-%20Bluebird.mp3</AUDIO></r>'
			],
		];
	}

	public static function getRenderingTests()
	{
		return [
			[
				'http://example.org/audio.mp3',
				'<div class="autoaudio"><p class="autoaudio-title"><a href="http://example.org/audio.mp3">audio.mp3</a>:</p><p class="autoaudio-player"><audio controls="" src="http://example.org/audio.mp3"></audio></p></div>'
			],
			[
				'http://example.org/my%20song.mp3',
				'<div class="autoaudio"><p class="autoaudio-title"><a href="http://example.org/my%20song.mp3">my song.mp3</a>:</p><p class="autoaudio-player"><audio controls="" src="http://example.org/my%20song.mp3"></audio></p></div>'
			],
		];
	}
}
