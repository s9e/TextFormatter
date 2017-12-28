<?php

namespace s9e\TextFormatter\Tests\Parser;

use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\Tag;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
*/
class TagProcessingTest extends Test
{
	/**
	* @testdox Works
	* @dataProvider getData
	*/
	public function test($original, $expected, $setup = null, $callback = null, array $expectedLogs = null)
	{
		$this->configurator->rulesGenerator->clear();
		$this->configurator->rulesGenerator->add('AllowAll');

		$this->assertParsing($original, $expected, $setup, $callback, $expectedLogs);
	}

	public function getData()
	{
		return [
			[
				'foo bar',
				'<r><X><Y>foo</Y> <Z>bar</Z></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
					$configurator->tags->add('Z');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addSelfClosingTag('Y', 0, 3);
					$parser->addSelfClosingTag('Z', 4, 3);
					$parser->addEndTag('X', 7, 0);
				}
			],
			[
				'foo bar',
				'<r>foo <X>bar</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 4, 0);
				}
			],
			[
				'foo bar',
				'<t>foo bar</t>',
				null,
				function ($parser)
				{
					$parser->addStartTag('X', 4, 0);
				}
			],
			[
				'foo bar',
				'<r><X>fo<Y>o</Y></X> bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
					$parser->addStartTag('Y', 2, 0);
					$parser->addEndTag('Y', 4, 1);
				}
			],
			[
				'foo bar',
				'<r><X>fo<Y>o</Y></X><Y> b</Y>ar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
					$parser->addStartTag('Y', 2, 0);
					$parser->addEndTag('Y', 5, 0);
				}
			],
			[
				'foo bar',
				'<r><X>fo<Y attr="foo">o</Y></X><Y attr="foo"> b</Y>ar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$tag = $configurator->tags->add('Y');
					$tag->attributes->add('attr')->required = false;
					$tag->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
					$parser->addStartTag('Y', 2, 0)->setAttribute('attr', 'foo');
					$parser->addEndTag('Y', 5, 0);
				}
			],
			[
				'x [b][i]...[/b][/i] y',
				'<r>x <B><s>[b]</s><I><s>[i]</s>...</I><e>[/b]</e></B><i>[/i]</i> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('I')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('B', 2, 3);
					$parser->addStartTag('I', 5, 3);
					$parser->addEndTag('B', 11, 4);
					$parser->addEndTag('I', 15, 4);
				}
			],
			[
				'x [b][i]...[/b]![/i] y',
				'<r>x <B><s>[b]</s><I><s>[i]</s>...</I><e>[/b]</e></B><I>!<e>[/i]</e></I> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('I')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('B', 2, 3);
					$parser->addStartTag('I', 5, 3);
					$parser->addEndTag('B', 11, 4);
					$parser->addEndTag('I', 16, 4);
				}
			],
			[
				'x [b][i][u]...[/b][/u][/i] y',
				'<r>x <B><s>[b]</s><I><s>[i]</s><U><s>[u]</s>...</U></I><e>[/b]</e></B><i>[/u][/i]</i> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('I')->rules->autoReopen();
					$configurator->tags->add('U')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('B', 2, 3);
					$parser->addStartTag('I', 5, 3);
					$parser->addStartTag('U', 8, 3);
					$parser->addEndTag('B', 14, 4);
					$parser->addEndTag('U', 18, 4);
					$parser->addEndTag('I', 22, 4);
				}
			],
			[
				'x [b][i][u]...[/b][/u][/i] y',
				'<r>x <B><s>[b]</s><I><s>[i]</s><U><s>[u]</s>...</U></I><e>[/b]</e></B><i>[/u][/i]</i> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('I');
					$configurator->tags->add('U');
				},
				function ($parser)
				{
					$parser->addStartTag('B', 2, 3);
					$parser->addStartTag('I', 5, 3);
					$parser->addStartTag('U', 8, 3);
					$parser->addEndTag('B', 14, 4);
					$parser->addEndTag('U', 18, 4);
					$parser->addEndTag('I', 22, 4);
				}
			],
			[
				'x [b][i][u]...[/b][/i][/u] y',
				'<r>x <B><s>[b]</s><I><s>[i]</s><U><s>[u]</s>...</U></I><e>[/b]</e></B><i>[/i][/u]</i> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('I')->rules->autoReopen();
					$configurator->tags->add('U')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('B', 2, 3);
					$parser->addStartTag('I', 5, 3);
					$parser->addStartTag('U', 8, 3);
					$parser->addEndTag('B', 14, 4);
					$parser->addEndTag('I', 18, 4);
					$parser->addEndTag('U', 22, 4);
				}
			],
			[
				'x [b][i][u]...[/b][/i][/u] y',
				'<r>x <B><s>[b]</s><I><s>[i]</s><U><s>[u]</s>...</U></I><e>[/b]</e></B>[/i][/u] y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('I')->rules->autoReopen();
					$configurator->tags->add('U')->rules->autoReopen();
				},
				function ($parser)
				{
					// Set maxFixingCost to 2 so that it allows [u] and [i] to be closed, without
					// spending any efforts on reopening them
					$parser->maxFixingCost = 2;

					$parser->addStartTag('B', 2, 3);
					$parser->addStartTag('I', 5, 3);
					$parser->addStartTag('U', 8, 3);
					$parser->addEndTag('B', 14, 4);
					$parser->addEndTag('I', 18, 4);
					$parser->addEndTag('U', 22, 4);
				}
			],
			[
				'x [b][i][u]...[/b][/i]u[/u] y',
				'<r>x <B><s>[b]</s><I><s>[i]</s><U><s>[u]</s>...</U></I><e>[/b]</e></B><i>[/i]</i><U>u<e>[/u]</e></U> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('I')->rules->autoReopen();
					$configurator->tags->add('U')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('B', 2, 3);
					$parser->addStartTag('I', 5, 3);
					$parser->addStartTag('U', 8, 3);
					$parser->addEndTag('B', 14, 4);
					$parser->addEndTag('I', 18, 4);
					$parser->addEndTag('U', 23, 4);
				}
			],
			[
				'x [i][b][u]...[/b][/i][/u] y',
				'<r>x <I><s>[i]</s><B><s>[b]</s><U><s>[u]</s>...</U><e>[/b]</e></B><e>[/i]</e></I><i>[/u]</i> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('B')->rules->autoReopen();
					$configurator->tags->add('I')->rules->autoReopen();
					$configurator->tags->add('U')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('I', 2, 3);
					$parser->addStartTag('B', 5, 3);
					$parser->addStartTag('U', 8, 3);
					$parser->addEndTag('B', 14, 4);
					$parser->addEndTag('I', 18, 4);
					$parser->addEndTag('U', 22, 4);
				}
			],
			[
				'x [i][color=red][u]...[/color][/i][/u] y',
				'<r>x <I><s>[i]</s><COLOR color="red"><s>[color=red]</s><U><s>[u]</s>...</U><e>[/color]</e></COLOR><e>[/i]</e></I><i>[/u]</i> y</r>',
				function ($configurator)
				{
					$configurator->tags->add('COLOR')->rules->autoReopen();
					$configurator->tags['COLOR']->attributes->add('color');
					$configurator->tags->add('I')->rules->autoReopen();
					$configurator->tags->add('U')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('I', 2, 3);
					$parser->addStartTag('COLOR', 5, 11)->setAttribute('color', 'red');
					$parser->addStartTag('U', 16, 3);
					$parser->addEndTag('COLOR', 22, 8);
					$parser->addEndTag('I', 30, 4);
					$parser->addEndTag('U', 34, 4);
				}
			],
			[
				'foo bar',
				'<r>foo <X>bar</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 4, 0);
					$parser->addEndTag('Y', 5, 0);
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
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			],
			[
				'fooo bar',
				'<r><X>f<X>oo</X>o</X> bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->nestingLimit = 2;
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('X', 1, 0);
					$parser->addSelfClosingTag('X', 2, 1);
					$parser->addEndTag('X', 3, 0);
					$parser->addEndTag('X', 4, 0);
				},
				[
					[
						'err',
						'Nesting limit exceeded',
						[
							'tag'          => $this->runClosure(
								function ()
								{
									$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 2, 1);
									$tag->invalidate();

									return $tag;
								}
							),
							'tagName'      => 'X',
							'nestingLimit' => 2
						]
					]
				]
			],
			[
				'foo bar',
				'<r><X>f</X><X>o</X>o bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->tagLimit = 2;
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 1, 1);
					$parser->addSelfClosingTag('X', 2, 1);
				},
				[
					[
						'err',
						'Tag limit exceeded',
						[
							'tag'      => $this->runClosure(
								function ()
								{
									$tag = new Tag(Tag::SELF_CLOSING_TAG, 'X', 2, 1);
									$tag->invalidate();

									return $tag;
								}
							),
							'tagName'  => 'X',
							'tagLimit' => 2
						]
					]
				]
			],
			[
				'foo bar',
				'<r><X>foo</X> bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addSelfClosingTag('Y', 1, 1)
					       ->cascadeInvalidationTo($parser->addSelfClosingTag('Y', 5, 1));
				}
			],
			[
				"[pre]foo[b]x\ny[/b]bar[/pre]a\nb",
				"<r><PRE><s>[pre]</s>foo<B><s>[b]</s>x<br/>\ny<e>[/b]</e></B>bar<e>[/pre]</e></PRE>a<br/>\nb</r>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('PRE')->rules->suspendAutoLineBreaks();
					$configurator->tags->add('B');
				},
				function ($parser)
				{
					$parser->addStartTag('PRE', 0, 5);
					$parser->addEndTag('PRE', 21, 6);
					$parser->addStartTag('B', 8, 3);
					$parser->addEndTag('B', 14, 4);
				}
			],
			[
				"[pre]foo[b]x\ny[/b]bar[/pre]a\nb",
				"<r><PRE><s>[pre]</s>foo<B><s>[b]</s>x\ny<e>[/b]</e></B>bar<e>[/pre]</e></PRE>a<br/>\nb</r>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('PRE')->rules->disableAutoLineBreaks();
					$configurator->tags->add('B');
				},
				function ($parser)
				{
					$parser->addStartTag('PRE', 0, 5);
					$parser->addEndTag('PRE', 21, 6);
					$parser->addStartTag('B', 8, 3);
					$parser->addEndTag('B', 14, 4);
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
					$parser->addIgnoreTag(2, 1);
				}
			],
			[
				'foo bar',
				'<r><X>foo</X><i> b</i>ar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addIgnoreTag(2, 3);
				}
			],
			[
				'foo bar',
				'<r>foo <X>bar</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 4, 3);
				}
			],
			[
				'foo bar',
				'<t>foo bar</t>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 4, 4);
				}
			],
			[
				'foo bar',
				'<r><X>foo bar</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 7);
				}
			],
			[
				'foo bar',
				'<t>foo bar</t>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 8);
				}
			],
			[
				'*foo* bar',
				'<r><X><s>*</s>foo<e>*</e></X> bar</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 4, 1));
				}
			],
			[
				'*foo* bar',
				'<r><X><s>*</s>foo* bar</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 99, 1));
				}
			],
			[
				'*foo* bar',
				'<r><X><s>*</s>foo* bar</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 99, 1));
					$parser->addEndTag('X', 4, 1);
				}
			],
			[
				'*_foo* bar_',
				'<r><X><s>*</s><Y><s>_</s>foo</Y><e>*</e></X><Y> bar<e>_</e></Y></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 5, 1));
					$parser->addStartTag('Y', 1, 1)
					       ->pairWith($parser->addEndTag('Y', 10, 1));
					$parser->addEndTag('Y', 6, 1);
				}
			],
			[
				'**x**x***',
				'<r><X><s>**</s>x<Y><s>**</s>x<e>**</e></Y><e>*</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 2)
					       ->pairWith($parser->addEndTag('X', 7, 2));
					$parser->addStartTag('Y', 3, 2)
					       ->pairWith($parser->addEndTag('Y', 6, 2));
				}
			],
			[
				'**x[**]x',
				'<r><X><s>**</s>x<Y>[**]</Y></X>x</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 2)
					       ->pairWith($parser->addEndTag('X', 4, 2));
					$parser->addSelfClosingTag('Y', 3, 4);
				}
			],
			[
				'xy',
				'<t>x<br/>y</t>',
				null,
				function ($parser)
				{
					$parser->addBrTag(1);
				}
			],
			[
				'xy',
				'<t>xy</t>',
				function ($configurator)
				{
					$configurator->rootRules->preventLineBreaks();
				},
				function ($parser)
				{
					$parser->addBrTag(1);
				}
			],
			[
				'xx',
				'<r><X>x</X><X>x</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->closeParent('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('X', 1, 0);
				}
			],
			[
				'xx [hr] yy',
				'<r>xx <HR>[hr]</HR> yy</r>',
				function ($configurator)
				{
					$configurator->tags->add('HR')->rules->autoClose();
				},
				function ($parser)
				{
					$parser->addStartTag('HR', 3, 4);
				}
			],
			[
				'xx [hr][/hr] yy',
				'<r>xx <HR><s>[hr]</s><e>[/hr]</e></HR> yy</r>',
				function ($configurator)
				{
					$configurator->tags->add('HR')->rules->autoClose();
				},
				function ($parser)
				{
					$parser->addStartTag('HR', 3, 4);
					$parser->addEndTag('HR', 7, 5);
				}
			],
			[
				'xx [img=foo.png] yy',
				'<r>xx <IMG src="foo.png">[img=foo.png]</IMG> yy</r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('IMG');
					$tag->attributes->add('src');
					$tag->rules->autoClose();
				},
				function ($parser)
				{
					$parser->addStartTag('IMG', 3, 13)->setAttribute('src', 'foo.png');
				}
			],
			[
				'xx [img]foo.png[/img] yy',
				'<r>xx <IMG src="foo.png"><s>[img]</s>foo.png<e>[/img]</e></IMG> yy</r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('IMG');
					$tag->attributes->add('src');
					$tag->rules->autoClose();
				},
				function ($parser)
				{
					$tag = $parser->addStartTag('IMG', 3, 5);
					$tag->setAttribute('src', 'foo.png');

					$tag->pairWith($parser->addEndTag('IMG', 15, 6));
				}
			],
			[
				'XYX',
				'<r><X><s>X</s><Y>Y</Y><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addSelfClosingTag('Y', 1, 1);
					$parser->addEndTag('X', 2, 1);
				}
			],
			[
				'XYX',
				'<r><X><s>X</s>Y<e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyChild('Y');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addSelfClosingTag('Y', 1, 1);
					$parser->addEndTag('X', 2, 1);
				}
			],
			[
				'XYZYX',
				'<r><X><s>X</s><Y><s>Y</s><Z>Z</Z><e>Y</e></Y><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyChild('Z');
					$configurator->tags->add('Y');
					$configurator->tags->add('Z');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addStartTag('Y', 1, 1);
					$parser->addSelfClosingTag('Z', 2, 1);
					$parser->addEndTag('Y', 3, 1);
					$parser->addEndTag('X', 4, 1);
				}
			],
			[
				'XYZYX',
				'<r><X><s>X</s><Y><s>Y</s>Z<e>Y</e></Y><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyChild('Z');
					$configurator->tags->add('Y')->rules->isTransparent();
					$configurator->tags->add('Z');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addStartTag('Y', 1, 1);
					$parser->addSelfClosingTag('Z', 2, 1);
					$parser->addEndTag('Y', 3, 1);
					$parser->addEndTag('X', 4, 1);
				}
			],
			[
				'XYX',
				'<r><X><s>X</s>Y<e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyChild('Y');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addSelfClosingTag('Y', 1, 1);
					$parser->addEndTag('X', 2, 1);
				}
			],
			[
				'XYX',
				'<r><X><s>X</s><Y>Y</Y><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyDescendant('Y');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addSelfClosingTag('Y', 1, 1);
					$parser->addEndTag('X', 2, 1);
				}
			],
			[
				'XYZYX',
				'<r><X><s>X</s><Y><s>Y</s>Z<e>Y</e></Y><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyDescendant('Z');
					$configurator->tags->add('Y');
					$configurator->tags->add('Z');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addStartTag('Y', 1, 1);
					$parser->addSelfClosingTag('Z', 2, 1);
					$parser->addEndTag('Y', 3, 1);
					$parser->addEndTag('X', 4, 1);
				}
			],
			[
				'XYX',
				'<r><X><s>X</s>Y<e>X</e></X></r>',
				function ($configurator)
				{
					$rules = $configurator->tags->add('X')->rules;
					$rules->isTransparent();
					$rules->denyChild('Y');

					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addSelfClosingTag('Y', 1, 1);
					$parser->addEndTag('X', 2, 1);
				}
			],
			[
				'XYYYX',
				'<r><X><s>X</s><Y><s>Y</s><Y><s>Y</s><Y><s>Y</s></Y></Y></Y><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->maxFixingCost = 0;

					$parser->addStartTag('X', 0, 1);
					$parser->addStartTag('Y', 1, 1);
					$parser->addStartTag('Y', 2, 1);
					$parser->addStartTag('Y', 3, 1);
					$parser->addEndTag('X', 4, 1);
				},
				[['warn', 'Fixing cost limit exceeded']]
			],
			[
				'XYYYYX',
				'<r><X><s>X</s><Y><s>Y</s><Y>Y</Y><Y>Y</Y><e>Y</e></Y><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					// Ensuring that we can still parse well-formed text if we disallow any fixing
					$parser->maxFixingCost = 0;

					$parser->addStartTag('X', 0, 1);
					$parser->addStartTag('Y', 1, 1);
					$parser->addSelfClosingTag('Y', 2, 1);
					$parser->addSelfClosingTag('Y', 3, 1);
					$parser->addEndTag('Y', 4, 1);
					$parser->addEndTag('X', 5, 1);
				}
			],
			[
				'..',
				'<r><X>.</X><X>.</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					// NOTE: the tags are added in order of position, but the stack still needs to
					//       be sorted following the right tiebreakers. This is what we're testing
					$parser->addEndTag('X', 2, 0);
					$parser->addEndTag('X', 1, 0);
					$parser->addStartTag('X', 1, 0);
					$parser->addStartTag('X', 0, 0);
				}
			],
			[
				'...',
				'<r><X>.</X><X>.</X><X>.</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain
						->append(__CLASS__ . '::addXTagAtStartCallback')
						->addParameterByName('parser')
						->addParameterByValue(2)
						->addParameterByValue(1);
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			],
			[
				'...',
				'<r><X>.</X><X>.</X><X>.</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain
						->append(__CLASS__ . '::addXTagAtStartCallback')
						->addParameterByName('parser')
						->addParameterByValue(1)
						->addParameterByValue(1);
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 2, 1);
				}
			],
			[
				'[UL]
					[*] foo
					[*] bar
				[/UL]',
				'<r><UL><s>[UL]</s>
					<LI><s>[*]</s> foo</LI>
					<LI><s>[*]</s> bar</LI>
				<e>[/UL]</e></UL></r>',
				function ($configurator)
				{
					$configurator->tags->add('UL');
					$rules = $configurator->tags->add('LI')->rules;
					$rules->closeAncestor('LI');
					$rules->ignoreSurroundingWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('UL', 0, 4);
					$parser->addStartTag('LI', 10, 3);
					$parser->addStartTag('LI', 23, 3);
					$parser->addEndTag('UL', 35, 5);
				}
			],
			[
				'[UL]
					[*] foo
					[*] bar
				[/UL]',
				'<r><UL><s>[UL]</s>
					<LI><s>[*]</s> foo</LI>
					<LI><s>[*]</s> bar</LI>
				<e>[/UL]</e></UL></r>',
				function ($configurator)
				{
					$configurator->tags->add('UL');
					$rules = $configurator->tags->add('LI')->rules;
					$rules->closeParent('LI');
					$rules->ignoreSurroundingWhitespace();
				},
				function ($parser)
				{
					$parser->addStartTag('UL', 0, 4);
					$parser->addStartTag('LI', 10, 3);
					$parser->addStartTag('LI', 23, 3);
					$parser->addEndTag('UL', 35, 5);
				}
			],
			[
				'XX',
				'<r>X<X>X</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain
						->append(__CLASS__ . '::invalidateTagsAtPos0');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			],
			[
				'XYX',
				'<r><X><s>X</s>Y<e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyChild('Y');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addSelfClosingTag('Y', 1, 1);
					$parser->addEndTag('X', 2, 1);
				},
				[
					[
						'warn',
						'Tag is not allowed in this context',
						[
							'tagName' => 'Y',
							'tag'     => $this->runClosure(
								function ()
								{
									$tag = new Tag(Tag::SELF_CLOSING_TAG, 'Y', 1, 1);
									$tag->invalidate();

									return $tag;
								}
							),
						]
					]
				]
			],
			[
				'XX',
				'<r><X><s>X</s><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->denyChild('Y');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1);
					$parser->addSelfClosingTag('Y', 1, 0);
					$parser->addEndTag('X', 1, 1);
				},
				[
					[
						'debug',
						'Tag is not allowed in this context',
						[
							'tagName' => 'Y',
							'tag'     => $this->runClosure(
								function ()
								{
									$tag = new Tag(Tag::SELF_CLOSING_TAG, 'Y', 1, 0);
									$tag->invalidate();

									return $tag;
								}
							),
						]
					]
				]
			],
			[
				'X',
				'<t>X</t>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->requireAncestor('Y');
					$configurator->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
				}
			],
			[
				'.X.',
				'<r><NOPARSE><s>.</s>X<e>.</e></NOPARSE></r>',
				function ($configurator)
				{
					$configurator->tags->add('NOPARSE')->rules->ignoreTags();
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addTagPair('NOPARSE', 0, 1, 2, 1);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			],
			[
				'.X.',
				'<r><NOPARSE><s>.</s>X<e>.</e></NOPARSE></r>',
				function ($configurator)
				{
					$configurator->tags->add('NOPARSE')->rules->ignoreTags();
					$configurator->tags->add('X')->rules->closeParent('NOPARSE');
				},
				function ($parser)
				{
					$parser->addTagPair('NOPARSE', 0, 1, 2, 1);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			],
			[
				'X.X.X',
				'<r><X><s>X</s><NOPARSE><s>.</s>X<e>.</e></NOPARSE><e>X</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('NOPARSE')->rules->ignoreTags();
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addTagPair('NOPARSE', 1, 1, 3, 1);
					$parser->addStartTag('X', 0, 1);
					$parser->addEndTag('X', 2, 1);
					$parser->addEndTag('X', 4, 1);
				}
			],
			[
				'.X.',
				'<r><NOPARSE>.<i>X</i>.</NOPARSE></r>',
				function ($configurator)
				{
					$configurator->tags->add('NOPARSE')->rules->ignoreTags();
				},
				function ($parser)
				{
					$parser->addTagPair('NOPARSE', 0, 0, 3, 0);
					$parser->addIgnoreTag(1, 1);
				}
			],
			[
				'.XX.',
				'<r><NOPARSE><p>.X</p><p>X.</p></NOPARSE></r>',
				function ($configurator)
				{
					$tag = $configurator->tags->add('NOPARSE');
					$tag->rules->createParagraphs();
					$tag->rules->ignoreTags();
				},
				function ($parser)
				{
					$parser->addTagPair('NOPARSE', 0, 0, 4, 0);
					$parser->addParagraphBreak(2);
				}
			],
			[
				'.X.',
				'<r><NOPARSE>.<br/>X.</NOPARSE></r>',
				function ($configurator)
				{
					$configurator->tags->add('NOPARSE')->rules->ignoreTags();
				},
				function ($parser)
				{
					$parser->addTagPair('NOPARSE', 0, 0, 3, 0);
					$parser->addBrTag(1);
				}
			],
			[
				'   ',
				'<r><NOTAGS>   </NOTAGS></r>',
				function ($configurator)
				{
					$configurator->tags->add('NOTAGS')->rules->ignoreTags();
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addTagPair('NOTAGS', 0, 0, 3, 0);
					$parser->addSelfClosingTag('X', 1, 0)->cascadeInvalidationTo($parser->addBrTag(2));
				}
			],
			[
				'foobar',
				'<t><p>foo</p><p>bar</p></t>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
				},
				function ($parser)
				{
					$parser->addParagraphBreak(3);
				}
			],
			[
				'foo|bar',
				'<r><p>foo</p><i>|</i><p>bar</p></r>',
				function ($configurator)
				{
					$configurator->rootRules->createParagraphs();
				},
				function ($parser)
				{
					$parser->addParagraphBreak(3);
					$parser->addIgnoreTag(3, 1);
				}
			],
			[
				"\n\n",
				"<r><X><br/>\n</X><X>\n</X></r>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addTagPair('X', 0, 0, 1, 0);
					$parser->addTagPair('X', 1, 0, 2, 0)->setFlags(Parser::RULE_SUSPEND_AUTO_BR);
				}
			],
			[
				'xxx',
				"<r><T8>x<T8>x</T8>x</T8></r>",
				function ($configurator)
				{
					// Create 9 tags with different rules so that they don't occupy the same bit in
					// the allowed tags bitfields
					for ($i = 0; $i <= 8; ++$i)
					{
						$j = ($i + 1) % 9;
						$configurator->tags->add('T' . $i)->rules->denyChild('T' . $j);
					}
				},
				function ($parser)
				{
					$parser->addTagPair('T8', 0, 0, 3, 0);
					$parser->addSelfClosingTag('T8', 1, 1);
				}
			],
			[
				'[x][y]..[/y][/x]',
				'<r><X><s>[x]</s></X><Y><s>[y]</s><X>..</X><e>[/y]</e></Y><i>[/x]</i></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->autoReopen();
					$configurator->tags->add('Y')->rules
						->closeParent('X')
						->fosterParent('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 3);
					$parser->addStartTag('Y', 3, 3);
					$parser->addEndTag('Y', 8, 4);
					$parser->addEndTag('X', 12, 4);
				}
			],
			[
				"foo\nbar",
				"<t>foo\nbar</t>"
			],
			[
				"foo\nbar",
				"<t>foo<br/>\nbar</t>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
			[
				"foo\nbar",
				"<t>foo\nbar</t>",
				function ($configurator)
				{
					$configurator->rootRules->disableAutoLineBreaks();
					$configurator->rootRules->enableAutoLineBreaks();
				}
			],
			[
				// Automatic line breaks can be turned on and off repeatedly
				"[Y]\n[N]\n[Y]\n[N]\n[/N]\n[/Y]\n[/N]\n[/Y]",
				"<r><Y><s>[Y]</s><br/>\n<N><s>[N]</s>\n<Y><s>[Y]</s><br/>\n<N><s>[N]</s>\n<e>[/N]</e></N><br/>\n<e>[/Y]</e></Y>\n<e>[/N]</e></N><br/>\n<e>[/Y]</e></Y></r>",
				function ($configurator)
				{
					$configurator->tags->add('Y')->rules->enableAutoLineBreaks();
					$configurator->tags->add('N')->rules->disableAutoLineBreaks();
				},
				function ($parser)
				{
					$parser->addStartTag('Y', 0, 3);
					$parser->addStartTag('N', 4, 3);
					$parser->addStartTag('Y', 8, 3);
					$parser->addStartTag('N', 12, 3);
					$parser->addEndTag('N', 16, 4);
					$parser->addEndTag('Y', 21, 4);
					$parser->addEndTag('N', 26, 4);
					$parser->addEndTag('Y', 31, 4);
				}
			],
			[
				// Automatic line breaks can be temporarily suspended in current context only
				"[Y]\n[S]\n[X]\n[/X]\n[/S]\n[/Y]",
				"<r><Y><s>[Y]</s><br/>\n<S><s>[S]</s>\n<X><s>[X]</s><br/>\n<e>[/X]</e></X>\n<e>[/S]</e></S><br/>\n<e>[/Y]</e></Y></r>",
				function ($configurator)
				{
					$configurator->tags->add('Y')->rules->enableAutoLineBreaks();
					$configurator->tags->add('S')->rules->suspendAutoLineBreaks();
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('Y', 0, 3);
					$parser->addStartTag('S', 4, 3);
					$parser->addStartTag('X', 8, 3);
					$parser->addEndTag('X', 12, 4);
					$parser->addEndTag('S', 17, 4);
					$parser->addEndTag('Y', 22, 4);
				}
			],
			[
				'...',
				'<t>...</t>',
				function ($configurator)
				{
					$configurator->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 1, 1);
					$parser->addVerbatim(0, 3);
				}
			],
			[
				".\n.\n",
				"<t>.\n.<br/>\n</t>",
				function ($configurator)
				{
					$configurator->rootRules->enableAutoLineBreaks();
				},
				function ($parser)
				{
					$parser->addVerbatim(1, 1)->setFlags(0);
				}
			],
			[
				".\n.\n",
				"<t>.<br/>\n.\n</t>",
				function ($configurator)
				{
					$configurator->rootRules->disableAutoLineBreaks();
				},
				function ($parser)
				{
					$parser->addVerbatim(1, 1)->setFlags(Parser::RULE_ENABLE_AUTO_BR);
				}
			],
			[
				'xxx',
				'<r><i>xxx</i></r>',
				null,
				function ($parser)
				{
					$parser->addIgnoreTag(0, 99);
				}
			],
			[
				'xi',
				'<r><X>x</X><i>i</i><br/></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain
						->append(__CLASS__ . '::addBrTagCallback')
						->addParameterByName('parser')
						->addParameterByValue(2);
				},
				function ($parser)
				{
					$parser->addTagPair('X', 0, 0, 1, 0);
					$parser->addIgnoreTag(1, 1);
				}
			],
			[
				'[list]
					[*][b]...
					[*]...[/b]
				[/list]',
				'<r><LIST><s>[list]</s>
					<LI><s>[*]</s><B><s>[b]</s>...</B></LI>
					<LI><s>[*]</s><B>...<e>[/b]</e></B></LI>
				<e>[/list]</e></LIST></r>',
				function ($configurator)
				{
					$configurator->tags->add('LIST');
					$configurator->tags->add('LI')->rules->closeParent('LI')->fosterParent('B')->ignoreSurroundingWhitespace();
					$configurator->tags->add('B')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addTagPair('LIST', 0, 6, 42, 7);
					$parser->addStartTag('LI', 12, 3);
					$parser->addStartTag('B', 15, 3);
					$parser->addStartTag('LI', 27, 3);
					$parser->addEndTag('B', 33, 4);
				}
			],
			[
				'[list][*][b]... [/list]',
				'<r><LIST><s>[list]</s><LI><s>[*]</s><B><s>[b]</s>...</B></LI> <e>[/list]</e></LIST></r>',
				function ($configurator)
				{
					$configurator->tags->add('LIST')->rules->ignoreSurroundingWhitespace();
					$configurator->tags->add('LI');
					$configurator->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('LIST', 0, 6, 16, 7);
					$parser->addStartTag('LI', 6, 3);
					$parser->addStartTag('B', 9, 3);
				}
			],
			[
				'[list][*][b]... [/list]',
				'<r><LIST><s>[list]</s><LI><s>[*]</s><B><s>[b]</s>...</B></LI> <e>[/list]</e></LIST></r>',
				function ($configurator)
				{
					$configurator->tags->add('LIST');
					$configurator->tags->add('LI')->rules->ignoreSurroundingWhitespace();
					$configurator->tags->add('B');
				},
				function ($parser)
				{
					$parser->addTagPair('LIST', 0, 6, 16, 7);
					$parser->addStartTag('LI', 6, 3);
					$parser->addStartTag('B', 9, 3);
				}
			],
		];
	}

	public static function addBrTagCallback($tag, $parser, $pos)
	{
		$parser->addBrTag($pos);

		return true;
	}

	public static function addXTagAtStartCallback($tag, $parser, $pos, $len)
	{
		if ($tag->getPos() === 0)
		{
			$parser->addSelfClosingTag('X', $pos, $len);
		}

		return true;
	}

	public static function invalidateTagsAtPos0($tag)
	{
		if (!$tag->getPos())
		{
			$tag->invalidate();
		}
	}
}