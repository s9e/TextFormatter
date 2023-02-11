<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoticons;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Emoticons\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoticons\Parser
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
				':)',
				'<r><E>:)</E></r>',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
			[
				':)',
				'<r><EMOTE>:)</EMOTE></r>',
				['tagName' => 'EMOTE'],
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
			[
				':)',
				'<r><E>:)</E></r>',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->notAfter = '\\w';
					$configurator->Emoticons->add(':)', '<img src="s.png"/>');
				}
			],
			[
				' :)',
				'<r> <E>:)</E></r>',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->notAfter = '\\w';
					$configurator->Emoticons->add(':)', '<img src="s.png"/>');
				}
			],
			[
				'x:)',
				'<t>x:)</t>',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->notAfter = '\\w';
					$configurator->Emoticons->add(':)', '<img src="s.png"/>');
				}
			],
		];
	}

	public static function getRenderingTests()
	{
		return [
			[
				':)',
				'<img src="s.png" alt=":)">',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
			[
				':)',
				'<img src="s.png" alt=":)">',
				['tagName' => 'EMOTE'],
				function ($configurator)
				{
					$configurator->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
			[
				":')",
				'<img src="s.png">',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->add(":')", '<img src="s.png"/>');
				}
			],
			[
				':")',
				'<img src="s.png">',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->add(':")', '<img src="s.png"/>');
				}
			],
			[
				'\':")',
				'<img src="s.png">',
				[],
				function ($configurator)
				{
					$configurator->Emoticons->add('\':")', '<img src="s.png"/>');
				}
			],
		];
	}
}