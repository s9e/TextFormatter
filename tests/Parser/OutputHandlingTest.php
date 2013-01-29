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
		$this->assertParsing($original, $expected, $setup, $callback);
	}

	public function getData()
	{
		return [
			[
				'Plain text',
				'<pt>Plain text</pt>'
			],
			[
				"Plain\ntext",
				"<pt>Plain<br/>\ntext</pt>"
			],
			[
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
			],
			[
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
			],
			[
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
			],
			[
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
			],
			[
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
			],
			[
				'foo bar',
				'<pt>foo<i> </i>bar</pt>',
				null,
				function ($parser)
				{
					$parser->addIgnoreTag(3, 1);
				}
			],
			[
				'foo  bar',
				'<pt>foo<i>  </i>bar</pt>',
				null,
				function ($parser)
				{
					$parser->addIgnoreTag(3, 1);
					$parser->addIgnoreTag(4, 1);
				}
			],
			[
				'foo bar',
				'<pt>foo<br/> bar</pt>',
				null,
				function ($parser)
				{
					$parser->addBrTag(3);
				}
			],
			[
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
			],
			[
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
			],
			[
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
					$parser->addSelfClosingTag('X', 0, 0)->setAttributes([
						'xx' => '"xx"',
						'yy' => '<>'
					]);
				}
			],
			[
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
			],
			[
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
					$parser->addStartTag('X', 0, 0)->setAttributes([
						'xx' => '"xx"',
						'yy' => '<>'
					]);
					$parser->addEndTag('X', 3, 0);
				}
			],
			[
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
			],
			[
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
			],
			[
				'xxx

				[UL]
					[LI]aaa[/LI]
					[LI]bbb[/LI]
				[/UL]

yyy',
				'<rt>xxx<i>

				</i><UL><st>[UL]</st><i>
					</i><LI><st>[LI]</st>aaa<et>[/LI]</et></LI><i>
					</i><LI><st>[LI]</st>bbb<et>[/LI]</et></LI><i>
				</i><et>[/UL]</et></UL><i>

</i>yyy</rt>',
				function ($constructor)
				{
					$constructor->tags->add('UL')->rules->trimWhitespace();
					$constructor->tags->add('LI')->rules->trimWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('UL', 9, 4);
					$parser->addStartTag('LI', 19, 4);
					$parser->addEndTag('LI', 26, 5);
					$parser->addStartTag('LI', 37, 4);
					$parser->addEndTag('LI', 44, 5);
					$parser->addEndTag('UL', 54, 5);
				}
			],
		];
	}
}