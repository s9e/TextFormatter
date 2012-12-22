<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoticons;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Emoticons\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoticons\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use RenderingTestsRunner;

	public function getParsingTests()
	{
		return array(
			array(
				':)',
				'<rt><E>:)</E></rt>',
				array(),
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
			array(
				':)',
				'<rt><EMOTE>:)</EMOTE></rt>',
				array('tagName' => 'EMOTE'),
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
		);
	}

	public function getRenderingTests()
	{
		return array(
			array(
				':)',
				'<img src="s.png" alt=":)">',
				array(),
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
			array(
				':)',
				'<img src="s.png" alt=":)">',
				array('tagName' => 'EMOTE'),
				function ($constructor)
				{
					$constructor->Emoticons->add(':)', '<img src="s.png" alt=":)"/>');
				}
			),
			array(
				":')",
				'<img src="s.png">',
				array(),
				function ($constructor)
				{
					$constructor->Emoticons->add(":')", '<img src="s.png"/>');
				}
			),
			array(
				':")',
				'<img src="s.png">',
				array(),
				function ($constructor)
				{
					$constructor->Emoticons->add(':")', '<img src="s.png"/>');
				}
			),
			array(
				'\':")',
				'<img src="s.png">',
				array(),
				function ($constructor)
				{
					$constructor->Emoticons->add('\':")', '<img src="s.png"/>');
				}
			),
		);
	}
}