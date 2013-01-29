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

	public function getParsingTests()
	{
		return [
			[
				':)',
				'<rt><E>:)</E></rt>',
				[],
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
			[
				':)',
				'<rt><EMOTE>:)</EMOTE></rt>',
				['tagName' => 'EMOTE'],
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
		];
	}

	public function getRenderingTests()
	{
		return [
			[
				':)',
				'<img src="s.png" alt=":)">',
				[],
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
			[
				':)',
				'<img src="s.png" alt=":)">',
				['tagName' => 'EMOTE'],
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			],
			[
				":')",
				'<img src="s.png">',
				[],
				function ($constructor)
				{
					$constructor->Emoticons->add(":')", '<img src="s.png"/>');
				}
			],
			[
				':")',
				'<img src="s.png">',
				[],
				function ($constructor)
				{
					$constructor->Emoticons->add(':")', '<img src="s.png"/>');
				}
			],
			[
				'\':")',
				'<img src="s.png">',
				[],
				function ($constructor)
				{
					$constructor->Emoticons->add('\':")', '<img src="s.png"/>');
				}
			],
		];
	}
}