<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
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
				'<t>Plain text</t>'
			],
			[
				"Plain\ntext",
				"<t>Plain<br/>\ntext</t>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
			[
				'foo bar',
				'<r><X/>foo bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 0);
				}
			],
			[
				'foo bar',
				'<r>foo<X/> bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 3, 0);
				}
			],
			[
				'foo bar',
				'<r>foo bar<X/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 7, 0);
				}
			],
			[
				'foo bar',
				'<r><X>foo</X> bar</r>',
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
				'<r>foo<X> </X>bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 3, 1);
				}
			],
			[
				'foo bar',
				'<r>foo<i> </i>bar</r>',
				null,
				function ($parser)
				{
					$parser->addIgnoreTag(3, 1);
				}
			],
			[
				'foo  bar',
				'<r>foo<i>  </i>bar</r>',
				null,
				function ($parser)
				{
					$parser->addIgnoreTag(3, 1);
					$parser->addIgnoreTag(4, 1);
				}
			],
			[
				'foo bar',
				'<t>foo<br/> bar</t>',
				null,
				function ($parser)
				{
					$parser->addBrTag(3);
				}
			],
			[
				'foo bar',
				'<r xmlns:foo="urn:s9e:TextFormatter:foo"><foo:X/>foo bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('foo:X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('foo:X', 0, 0);
				}
			],
			[
				'foo bar',
				'<r xmlns:foo="urn:s9e:TextFormatter:foo"><foo:X/>foo bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('foo:X');
					$configurator->tags->add('bar:X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('foo:X', 0, 0);
				}
			],
			[
				'foo bar',
				'<r><X xx="&quot;xx&quot;" yy="&lt;&gt;"/>foo bar</r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X');
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
				'<r><X><i>foo</i></X> bar</r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X')->rules->ignoreText();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
				}
			],
			[
				'foo bar baz',
				'<r><X><i>foo</i></X><X> </X>bar<X><i> baz</i></X></r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X')->rules->ignoreText();
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
				'[X][B].[/B].[HR][/X]',
				'<r><X><s>[X]</s><p><B><s>[B]</s>.<e>[/B]</e></B><i>.</i></p><HR>[HR]</HR><e>[/X]</e></X></r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X');
					$tag->rules->ignoreText();
					$tag->rules->createParagraphs();
					$configurator->tags->add('B');
					$configurator->tags->add('HR')->rules->breakParagraph();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 3);
					$parser->addStartTag('B', 3, 3);
					$parser->addEndTag('B', 7, 4);
					$parser->addSelfClosingTag('HR', 12, 4);
					$parser->addEndTag('X', 16, 4);
				}
			],
			[
				'foo bar',
				'<r><X xx="&quot;xx&quot;" yy="&lt;&gt;">foo</X> bar</r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X');
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
				"<r>xxx\n<DIV><s>[DIV]</s>\n...\n<e>[/DIV]</e></DIV>\nyyy</r>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('DIV')->rules->ignoreSurroundingWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('DIV', 4, 5);
					$parser->addEndTag('DIV', 14, 6);
				}
			],
			[
				"xxx\n\n[DIV]\n\n...\n\n[/DIV]\n\nyyy",
				"<r>xxx<br/>\n\n<DIV><s>[DIV]</s>\n<br/>\n...<br/>\n\n<e>[/DIV]</e></DIV>\n\nyyy</r>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('DIV')->rules->ignoreSurroundingWhitespace();
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
				'<r>xxx<br/>

				<UL><s>[UL]</s>
					<LI><s>[LI]</s>aaa<e>[/LI]</e></LI>
					<LI><s>[LI]</s>bbb<e>[/LI]</e></LI>
				<e>[/UL]</e></UL>

yyy</r>',
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('UL')->rules->ignoreSurroundingWhitespace();
					$configurator->tags->add('LI')->rules->ignoreSurroundingWhitespace();
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
				'<t><p>foo bar</p></t>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
				}
			],
			[
				"foo\nbar",
				"<t><p>foo<br/>\nbar</p></t>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
			[
				"foo\n\nbar",
				"<t><p>foo</p>\n\n<p>bar</p></t>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
			[
				"foo\n\n\n\n\nbar\n\n\n\n\nbaz",
				"<t><p>foo</p>\n\n\n\n\n<p>bar</p>\n\n\n\n\n<p>baz</p></t>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
			[
				'
[UL]
	[LI]foo
	[LI]bar
[/UL]

',
				'<r>
<UL><s>[UL]</s>
	<LI><s>[LI]</s>foo
	</LI><LI><s>[LI]</s>bar
</LI><e>[/UL]</e></UL>

</r>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
					$rules = $configurator->tags->add('UL')->rules;
					$rules->breakParagraph();
					$rules->ignoreSurroundingWhitespace();
					$configurator->tags->add('LI')->rules->ignoreSurroundingWhitespace();
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
				'<r><p>...</p>

<UL><s>[UL]</s>
	<LI><s>[LI]</s>foo
	</LI><LI><s>[LI]</s>bar
</LI><e>[/UL]</e></UL>

<p>xxx</p></r>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
					$rules = $configurator->tags->add('UL')->rules;
					$rules->breakParagraph();
					$rules->ignoreSurroundingWhitespace();
					$configurator->tags->add('LI')->rules->ignoreSurroundingWhitespace();
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
				'<r><p><B><s>[b]</s>...<e>[/b]</e></B></p></r>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$rules = $configurator->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('B', 0, 3, 6, 4);
				}
			],
			[
				"\n[b]...[/b]\n",
				"<r>\n<p><B><s>[b]</s>...<e>[/b]</e></B></p>\n</r>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
					$rules = $configurator->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('B', 1, 3, 7, 4);
				}
			],
			[
				"x\n[b]...[/b]\ny",
				"<r><p>x<br/>\n<B><s>[b]</s>...<e>[/b]</e></B><br/>\ny</p></r>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
					$rules = $configurator->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('B', 2, 3, 8, 4);
				}
			],
			[
				'[img]',
				'<r><p><IMG>[img]</IMG></p></r>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$rules = $configurator->tags->add('IMG');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('IMG', 0, 5);
				}
			],
			[
				"[x]\n\n\nxxx",
				"<r><p><X>[x]</X>\n<Y>\n</Y><br/>\nxxx</p></r>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('X')->rules->ignoreSurroundingWhitespace();
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addSelfClosingTag('Y', 4, 1);
				}
			],
			[
				"x\n\n\n\nx",
				"<r><p><X>x</X></p>\n\n\n\n<p><X>x</X></p></r>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 5, 1);
				}
			],
			[
				'      ',
				'<r>  <i>  </i>  </r>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
				},
				function ($parser)
				{
					$parser->addIgnoreTag(2, 2);
				}
			],
			[
				"foo\n  \nQUOTE\n  \nbar",
				"<r><p>foo</p>\n  \n<QUOTE>QUOTE</QUOTE>\n  \n<p>bar</p></r>",
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('QUOTE')->rules->breakParagraph();
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('QUOTE', 7, 5);
				}
			],
			[
				// Test the attribute order
				'X',
				'<r><X bar="2" baz="3" foo="1">X</X></r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('X');
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