<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\OutputHandling;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\OutputHandling
*/
class OutputHandlingTest extends Test
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

	/**
	* 
	*
	* @return void
	*/
	public function getData()
	{
		return array(
			array(
				'Plain text',
				'<pt>Plain text</pt>'
			),
			array(
				"Plain\ntext",
				"<pt>Plain<br/>\ntext</pt>"
			),
			array(
				'foo bar',
				'<rt><X/>foo bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 0);
				}
			),
			array(
				'foo bar',
				'<rt>foo<X/> bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 3, 0);
				}
			),
			array(
				'foo bar',
				'<rt>foo bar<X/></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 7, 0);
				}
			),
			array(
				'foo bar',
				'<rt><X>foo</X> bar</rt>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
				}
			),
			array(
				'foo bar',
				'<rt>foo<X> </X>bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 3, 1);
				}
			),
			array(
				'foo bar',
				'<rt>foo<i> </i>bar</rt>',
				null,
				function ($parser)
				{
					$parser->addIgnoreTag(3, 1);
				}
			),
			array(
				'foo bar',
				'<rt>foo<br/> bar</rt>',
				null,
				function ($parser)
				{
					$parser->addBrTag(3);
				}
			),
		);
	}
}