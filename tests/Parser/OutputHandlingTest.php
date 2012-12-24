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
			array(
				'foo bar',
				'<rt xmlns:foo="urn:s9e:TextFormatter:foo"><foo:X/>foo bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('foo:X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('foo:X', 0, 0);
				}
			),
			array(
				'foo bar',
				'<rt xmlns:foo="urn:s9e:TextFormatter:foo"><foo:X/>foo bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('foo:X');
					$constructor->tags->add('bar:X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('foo:X', 0, 0);
				}
			),
			array(
				'foo bar',
				'<rt><X xx="&quot;xx&quot;" yy="&lt;&gt;"/>foo bar</rt>',
				function ($constructor)
				{
					$tag = $constructor->tags->add('X');
					$tag->attributes->add('xx');
					$tag->attributes->add('yy');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 0)->setAttributes(array(
						'xx' => '"xx"',
						'yy' => '<>'
					));
				}
			),
			array(
				'foo bar',
				'<rt><X><i>foo</i></X> bar</rt>',
				function ($constructor)
				{
					$tag = $constructor->tags->add('X')->rules->ignoreText();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
				}
			),
			array(
				'foo bar',
				'<rt><X xx="&quot;xx&quot;" yy="&lt;&gt;">foo</X> bar</rt>',
				function ($constructor)
				{
					$tag = $constructor->tags->add('X');
					$tag->attributes->add('xx');
					$tag->attributes->add('yy');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0)->setAttributes(array(
						'xx' => '"xx"',
						'yy' => '<>'
					));
					$parser->addEndTag('X', 3, 0);
				}
			),
			array(
				"xxx\n[DIV]\n...\n[/DIV]\nyyy",
				"<rt>xxx<i>\n</i><DIV><st>[DIV]</st><i>\n</i>...<i>\n</i><et>[/DIV]</et></DIV><i>\n</i>yyy</rt>",
				function ($constructor)
				{
					$constructor->tags->add('DIV')->rules->trimWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('DIV', 4, 5);
					$parser->addEndTag('DIV', 14, 6);
				}
			),
			array(
				"xxx\n\n[DIV]\n\n...\n\n[/DIV]\n\nyyy",
				"<rt>xxx<i>\n\n</i><DIV><st>[DIV]</st><i>\n</i><br/>\n...<br/>\n<i>\n</i><et>[/DIV]</et></DIV><i>\n\n</i>yyy</rt>",
				function ($constructor)
				{
					$constructor->tags->add('DIV')->rules->trimWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('DIV', 5, 5);
					$parser->addEndTag('DIV', 17, 6);
				}
			),
		);
	}
}