<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\TagProcessing;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\TagProcessing
*/
class TagProcessingTest extends Test
{
	/**
	* @testdox Works
	* @dataProvider getData
	*/
	public function test($original, $expected, $setup = null, $callback = null)
	{
		$configurator = new Configurator;

		if (isset($setup))
		{
			call_user_func($setup, $configurator);
		}

		$parser = $configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($callback, $parser)
			{
				if (isset($callback))
				{
					call_user_func($callback, $parser);
				}
			}
		);

		$this->assertSame($expected, $parser->parse($original));
	}

	public function getData()
	{
		return array(
			array(
				'foo bar',
				'<rt><X><Y>foo</Y> <Z>bar</Z></X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y');
					$constructor->tags->add('Z');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addSelfClosingTag('Y', 0, 3);
					$parser->addSelfClosingTag('Z', 4, 3);
					$parser->addEndTag('X', 7, 0);
				}
			),
		);
	}
}