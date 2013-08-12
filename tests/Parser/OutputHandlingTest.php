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
				'<rt>foo<i> </i>bar</rt>',
				null,
				function ($parser)
				{
					$parser->addIgnoreTag(3, 1);
				}
			],
			[
				'foo  bar',
				'<rt>foo<i>  </i>bar</rt>',
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
				'foo bar baz',
				'<rt><X><i>foo</i></X><X> </X>bar<X><i> baz</i></X></rt>',
				function ($constructor)
				{
					$tag = $constructor->tags->add('X')->rules->ignoreText();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
					$parser->addStartTag('X', 3, 0);
					$parser->addEndTag('X', 4, 0);
					$parser->addStartTag('X', 7, 0);
					$parser->addEndTag('X', 11, 0);
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
				"<rt>xxx\n<DIV><st>[DIV]</st>\n...\n<et>[/DIV]</et></DIV>\nyyy</rt>",
				function ($constructor)
				{
					$constructor->tags->add('DIV')->rules->ignoreSurroundingWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('DIV', 4, 5);
					$parser->addEndTag('DIV', 14, 6);
				}
			],
			[
				"xxx\n\n[DIV]\n\n...\n\n[/DIV]\n\nyyy",
				"<rt>xxx\n\n<DIV><st>[DIV]</st>\n<br/>\n...<br/>\n\n<et>[/DIV]</et></DIV>\n\nyyy</rt>",
				function ($constructor)
				{
					$constructor->tags->add('DIV')->rules->ignoreSurroundingWhitespace();
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
				'<rt>xxx

				<UL><st>[UL]</st>
					<LI><st>[LI]</st>aaa<et>[/LI]</et></LI>
					<LI><st>[LI]</st>bbb<et>[/LI]</et></LI>
				<et>[/UL]</et></UL>

yyy</rt>',
				function ($constructor)
				{
					$constructor->tags->add('UL')->rules->ignoreSurroundingWhitespace();
					$constructor->tags->add('LI')->rules->ignoreSurroundingWhitespace();
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
			[
				'foo bar',
				'<pt><p>foo bar</p></pt>',
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
				}
			],
			[
				"foo\nbar",
				"<pt><p>foo<br/>\nbar</p></pt>",
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
				}
			],
			[
				"foo\n\nbar",
				"<pt><p>foo</p>\n\n<p>bar</p></pt>",
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
				}
			],
			[
				"foo\n\n\n\n\nbar\n\n\n\n\nbaz",
				"<pt><p>foo</p>\n\n\n\n\n<p>bar</p>\n\n\n\n\n<p>baz</p></pt>",
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
				}
			],
			[
				'
[UL]
	[LI]foo
	[LI]bar
[/UL]

',
				'<rt>
<UL><st>[UL]</st>
	<LI><st>[LI]</st>foo
	</LI><LI><st>[LI]</st>bar
</LI><et>[/UL]</et></UL>

</rt>',
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$rules = $constructor->tags->add('UL')->rules;
					$rules->breakParagraph();
					$rules->ignoreSurroundingWhitespace();
					$constructor->tags->add('LI')->rules->ignoreSurroundingWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('UL', 1, 4);
					$parser->addStartTag('LI', 7, 4);
					$parser->addEndTag('LI', 16, 0);
					$parser->addStartTag('LI', 16, 4);
					$parser->addEndTag('LI', 24, 0);
					$parser->addEndTag('UL', 24, 5);
				}
			],
			[
				'...

[UL]
	[LI]foo
	[LI]bar
[/UL]

xxx',
				'<rt><p>...</p>

<UL><st>[UL]</st>
	<LI><st>[LI]</st>foo
	</LI><LI><st>[LI]</st>bar
</LI><et>[/UL]</et></UL>

<p>xxx</p></rt>',
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$rules = $constructor->tags->add('UL')->rules;
					$rules->breakParagraph();
					$rules->ignoreSurroundingWhitespace();
					$constructor->tags->add('LI')->rules->ignoreSurroundingWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('UL', 5, 4);
					$parser->addStartTag('LI', 11, 4);
					$parser->addEndTag('LI', 20, 0);
					$parser->addStartTag('LI', 20, 4);
					$parser->addEndTag('LI', 28, 0);
					$parser->addEndTag('UL', 28, 5);
				}
			],
			[
				'[b]...[/b]',
				'<rt><p><B><st>[b]</st>...<et>[/b]</et></B></p></rt>',
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$rules = $constructor->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('B', 0, 3, 6, 4);
				}
			],
			[
				"\n[b]...[/b]\n",
				"<rt>\n<p><B><st>[b]</st>...<et>[/b]</et></B></p>\n</rt>",
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$rules = $constructor->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('B', 1, 3, 7, 4);
				}
			],
			[
				"x\n[b]...[/b]\ny",
				"<rt><p>x<br/>\n<B><st>[b]</st>...<et>[/b]</et></B><br/>\ny</p></rt>",
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$rules = $constructor->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('B', 2, 3, 8, 4);
				}
			],
			[
				'[img]',
				'<rt><p><IMG>[img]</IMG></p></rt>',
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$rules = $constructor->tags->add('IMG');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('IMG', 0, 5);
				}
			],
			[
				"[x]\n\n\nxxx",
				"<rt><p><X>[x]</X>\n<Y>\n</Y><br/>\nxxx</p></rt>",
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$constructor->tags->add('X')->rules->ignoreSurroundingWhitespace();
					$constructor->tags->add('Y')->rules->noBrDescendant();
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addSelfClosingTag('Y', 4, 1);
				}
			],
			[
				"x\n\n\n\nx",
				"<rt><p><X>x</X></p>\n\n\n\n<p><X>x</X></p></rt>",
				function ($constructor)
				{
					$constructor->rootRules->createParagraphs();
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 5, 1);
				}
			],
			[
				// Test the attribute order
				'X',
				'<rt><X bar="2" baz="3" foo="1">X</X></rt>',
				function ($constructor)
				{
					$tag = $constructor->tags->add('X');
					$tag->attributes->add('foo');
					$tag->attributes->add('bar');
					$tag->attributes->add('baz');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1)
					       ->setAttributes(['foo' => 1, 'bar' => 2, 'baz' => 3]);
				}
			],
		];
	}
}