<?php

namespace s9e\TextFormatter\Tests\Parser;

use RuntimeException;
use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\Tag;
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
	public function test($original, $expected, $setup = null, $callback = null, array $expectedLogs = null)
	{
		$this->assertParsing($original, $expected, $setup, $callback, $expectedLogs);
	}

	public function getData()
	{
		return [
			[
				'foo bar',
				'<rt><X><Y>foo</Y> <Z>bar</Z></X></rt>',
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
				'<rt>foo <X>bar</X></rt>',
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
				'<pt>foo bar</pt>',
				null,
				function ($parser)
				{
					$parser->addStartTag('X', 4, 0);
				}
			],
			[
				'foo bar',
				'<rt><X>fo<Y>o</Y></X> bar</rt>',
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
				'<rt><X>fo<Y>o</Y></X><Y> b</Y>ar</rt>',
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
				'<rt><X>fo<Y attr="foo">o</Y></X><Y attr="foo"> b</Y>ar</rt>',
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
				'<rt>x <B><st>[b]</st><I><st>[i]</st>...</I><et>[/b]</et></B><i>[/i]</i> y</rt>',
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
				'<rt>x <B><st>[b]</st><I><st>[i]</st>...</I><et>[/b]</et></B><I>!<et>[/i]</et></I> y</rt>',
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
				'<rt>x <B><st>[b]</st><I><st>[i]</st><U><st>[u]</st>...</U></I><et>[/b]</et></B><i>[/u][/i]</i> y</rt>',
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
				'<rt>x <B><st>[b]</st><I><st>[i]</st><U><st>[u]</st>...</U></I><et>[/b]</et></B><i>[/u][/i]</i> y</rt>',
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
				'<rt>x <B><st>[b]</st><I><st>[i]</st><U><st>[u]</st>...</U></I><et>[/b]</et></B><i>[/i][/u]</i> y</rt>',
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
				'<rt>x <B><st>[b]</st><I><st>[i]</st><U><st>[u]</st>...</U></I><et>[/b]</et></B>[/i][/u] y</rt>',
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
				'<rt>x <B><st>[b]</st><I><st>[i]</st><U><st>[u]</st>...</U></I><et>[/b]</et></B><i>[/i]</i><U>u<et>[/u]</et></U> y</rt>',
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
				'<rt>x <I><st>[i]</st><B><st>[b]</st><U><st>[u]</st>...</U><et>[/b]</et></B><et>[/i]</et></I><i>[/u]</i> y</rt>',
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
				'foo bar',
				'<rt>foo <X>bar</X></rt>',
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
				'<rt><X>foo</X> bar</rt>',
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
				'<rt><X>f<X>oo</X>o</X> bar</rt>',
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
				'<rt><X>f</X><X>o</X>o bar</rt>',
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
				'<rt><X>foo</X> bar</rt>',
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
				"<rt><PRE><st>[pre]</st>foo<B><st>[b]</st>x<br/>\ny<et>[/b]</et></B>bar<et>[/pre]</et></PRE>a<br/>\nb</rt>",
				function ($configurator)
				{
					$configurator->tags->add('PRE')->rules->noBrChild();
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
				"<rt><PRE><st>[pre]</st>foo<B><st>[b]</st>x\ny<et>[/b]</et></B>bar<et>[/pre]</et></PRE>a<br/>\nb</rt>",
				function ($configurator)
				{
					$configurator->tags->add('PRE')->rules->noBrDescendant();
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
				'<rt><X>foo</X> bar</rt>',
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
				'<rt><X>foo</X><i> b</i>ar</rt>',
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
				'<rt>foo <X>bar</X></rt>',
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
				'<pt>foo bar</pt>',
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
				'<rt><X>foo bar</X></rt>',
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
				'<pt>foo bar</pt>',
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
				'<rt><X><st>*</st>foo<et>*</et></X> bar</rt>',
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
				'<rt><X><st>*</st>foo* bar</X></rt>',
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
				'<rt><X><st>*</st>foo* bar</X></rt>',
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
				'<rt><X><st>*</st><Y><st>_</st>foo</Y><et>*</et></X><Y> bar<et>_</et></Y></rt>',
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
				'<rt><X><st>**</st>x<Y><st>**</st>x<et>**</et></Y><et>*</et></X></rt>',
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
				'<rt><X><st>**</st>x<Y>[**]</Y></X>x</rt>',
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
				'<pt>x<br/>y</pt>',
				null,
				function ($parser)
				{
					$parser->addBrTag(1);
				}
			],
			[
				'xx',
				'<rt><X>x</X><X>x</X></rt>',
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
				'<rt>xx <HR>[hr]</HR> yy</rt>',
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
				'xx [img=foo.png] yy',
				'<rt>xx <IMG src="foo.png">[img=foo.png]</IMG> yy</rt>',
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
				'<rt>xx <IMG src="foo.png"><st>[img]</st>foo.png<et>[/img]</et></IMG> yy</rt>',
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
				'<rt><X><st>X</st><Y>Y</Y><et>X</et></X></rt>',
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
				'<rt><X><st>X</st>Y<et>X</et></X></rt>',
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
				'<rt><X><st>X</st><Y><st>Y</st><Z>Z</Z><et>Y</et></Y><et>X</et></X></rt>',
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
				'<rt><X><st>X</st><Y><st>Y</st>Z<et>Y</et></Y><et>X</et></X></rt>',
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
				'<rt><X><st>X</st>Y<et>X</et></X></rt>',
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
				'<rt><X><st>X</st><Y><st>Y</st>Z<et>Y</et></Y><et>X</et></X></rt>',
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
				'<rt><X><st>X</st>Y<et>X</et></X></rt>',
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
				'XYYYYX',
				new RuntimeException('Fixing cost exceeded'),
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
					$parser->addStartTag('Y', 4, 1);
					$parser->addEndTag('X', 5, 1);
				}
			],
			[
				'XYYYYX',
				'<rt><X><st>X</st><Y><st>Y</st><Y>Y</Y><Y>Y</Y><et>Y</et></Y><et>X</et></X></rt>',
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
				'<rt><X>.</X><X>.</X></rt>',
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
				'<rt><X>.</X><X>.</X><X>.</X></rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain->append(
						function ($tag, $parser)
						{
							if ($tag->getPos() === 0)
							{
								$parser->addSelfClosingTag('X', 2, 1);
							}

							return true;
						}
					)->addParameterByName('parser');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			],
			[
				'...',
				'<rt><X>.</X><X>.</X><X>.</X></rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain->append(
						function ($tag, $parser)
						{
							if ($tag->getPos() === 0)
							{
								$parser->addSelfClosingTag('X', 1, 1);
							}

							return true;
						}
					)->addParameterByName('parser');
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
				'<rt><UL><st>[UL]</st>
					<LI><st>[*]</st> foo</LI>
					<LI><st>[*]</st> bar</LI>
				<et>[/UL]</et></UL></rt>',
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
				'<rt><UL><st>[UL]</st>
					<LI><st>[*]</st> foo</LI>
					<LI><st>[*]</st> bar</LI>
				<et>[/UL]</et></UL></rt>',
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
				'<rt>X<X>X</X></rt>',
				function ($configurator)
				{
					$configurator->tags->add('X')->filterChain->append(
						function ($tag)
						{
							return (bool) $tag->getPos();
						}
					);
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 1);
					$parser->addSelfClosingTag('X', 1, 1);
				}
			],
			[
				'XYX',
				'<rt><X><st>X</st>Y<et>X</et></X></rt>',
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
				'X',
				'<pt>X</pt>',
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
				'<rt><NOPARSE><st>.</st>X<et>.</et></NOPARSE></rt>',
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
				'<rt><NOPARSE><st>.</st>X<et>.</et></NOPARSE></rt>',
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
				'<rt><X><st>X</st><NOPARSE><st>.</st>X<et>.</et></NOPARSE><et>X</et></X></rt>',
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
				'foobar',
				'<pt><p>foo</p><p>bar</p></pt>',
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
				'<rt><p>foo</p><i>|</i><p>bar</p></rt>',
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
		];
	}
}