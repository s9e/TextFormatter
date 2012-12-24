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
			array(
				'foo bar',
				'<rt>foo <X>bar</X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 4, 0);
				}
			),
			array(
				'foo bar',
				'<pt>foo bar</pt>',
				null,
				function ($parser)
				{
					$parser->addStartTag('X', 4, 0);
				}
			),
			array(
				'foo bar',
				'<rt>foo <X>bar</X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 4, 0);
					$parser->addEndTag('Y', 5, 0);
				}
			),
			array(
				'foo bar',
				'<rt><X>foo</X> bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			),
			array(
				'fooo bar',
				'<rt><X>f<X>oo</X>o</X> bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->nestingLimit = 2;
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('X', 1, 0);
					$parser->addSelfClosingTag('X', 2, 1);
					$parser->addEndTag('X', 3, 0);
					$parser->addEndTag('X', 4, 0);
				}
			),
			array(
				'foo bar',
				'<rt><X>f</X><X>o</X>o bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->tagLimit = 2;
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 1, 1);
					$parser->addSelfClosingTag('X', 2, 1);
				}
			),
		);
	}
}