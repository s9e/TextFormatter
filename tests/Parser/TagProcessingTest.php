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
				'<rt><X>fo<Y>o</Y></X> bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
					$parser->addStartTag('Y', 2, 0);
					$parser->addEndTag('Y', 4, 1);
				}
			),
			array(
				'foo bar',
				'<rt><X>fo<Y>o</Y></X><Y> b</Y>ar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addEndTag('X', 3, 0);
					$parser->addStartTag('Y', 2, 0);
					$parser->addEndTag('Y', 5, 0);
				}
			),
			array(
				'foo bar',
				'<rt><X>fo<Y attr="foo">o</Y></X><Y attr="foo"> b</Y>ar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$tag = $constructor->tags->add('Y');
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
			array(
				'foo bar',
				'<rt><X>foo</X> bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addSelfClosingTag('Y', 1, 1)
					       ->cascadeInvalidationTo($parser->addSelfClosingTag('Y', 5, 1));
				}
			),
			array(
				"[pre]foo[b]x\ny[/b]bar[/pre]a\nb",
				"<rt><PRE><st>[pre]</st>foo<B><st>[b]</st>x<br/>\ny<et>[/b]</et></B>bar<et>[/pre]</et></PRE>a<br/>\nb</rt>",
				function ($constructor)
				{
					$constructor->tags->add('PRE')->rules->noBrChild();
					$constructor->tags->add('B');
				},
				function ($parser)
				{
					$parser->addStartTag('PRE', 0, 5);
					$parser->addEndTag('PRE', 21, 6);
					$parser->addStartTag('B', 8, 3);
					$parser->addEndTag('B', 14, 4);
				}
			),
			array(
				"[pre]foo[b]x\ny[/b]bar[/pre]a\nb",
				"<rt><PRE><st>[pre]</st>foo<B><st>[b]</st>x\ny<et>[/b]</et></B>bar<et>[/pre]</et></PRE>a<br/>\nb</rt>",
				function ($constructor)
				{
					$constructor->tags->add('PRE')->rules->noBrDescendant();
					$constructor->tags->add('B');
				},
				function ($parser)
				{
					$parser->addStartTag('PRE', 0, 5);
					$parser->addEndTag('PRE', 21, 6);
					$parser->addStartTag('B', 8, 3);
					$parser->addEndTag('B', 14, 4);
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
					$parser->addIgnoreTag(2, 1);
				}
			),
			array(
				'foo bar',
				'<rt><X>foo</X><i> b</i>ar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 0, 3);
					$parser->addIgnoreTag(2, 3);
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
					$parser->addSelfClosingTag('X', 4, 3);
				}
			),
			array(
				'foo bar',
				'<pt>foo bar</pt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addSelfClosingTag('X', 4, 4);
				}
			),
			array(
				'*foo* bar',
				'<rt><X><st>*</st>foo<et>*</et></X> bar</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 4, 1));
				}
			),
			array(
				'*foo* bar',
				'<rt><X><st>*</st>foo* bar</X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 99, 1));
				}
			),
			array(
				'*foo* bar',
				'<rt><X><st>*</st>foo* bar</X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 99, 1));
					$parser->addEndTag('X', 4, 1);
				}
			),
			array(
				'*_foo* bar_',
				'<rt><X><st>*</st><Y><st>_</st>foo</Y><et>*</et></X><Y> bar<et>_</et></Y></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y')->rules->autoReopen();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 1)
					       ->pairWith($parser->addEndTag('X', 5, 1));
					$parser->addStartTag('Y', 1, 1)
					       ->pairWith($parser->addEndTag('Y', 10, 1));
					$parser->addEndTag('Y', 6, 1);
				}
			),
			array(
				'**x**x***',
				'<rt><X><st>**</st>x<Y><st>**</st>x<et>**</et></Y><et>*</et></X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 2)
					       ->pairWith($parser->addEndTag('X', 7, 2));
					$parser->addStartTag('Y', 3, 2)
					       ->pairWith($parser->addEndTag('Y', 6, 2));
				}
			),
			array(
				'**x[**]x',
				'<rt><X><st>**</st>x<Y>[**]</Y></X>x</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 2)
					       ->pairWith($parser->addEndTag('X', 4, 2));
					$parser->addSelfClosingTag('Y', 3, 4);
				}
			),
			array(
				'xy',
				'<pt>x<br/>y</pt>',
				null,
				function ($parser)
				{
					$parser->addBrTag(1);
				}
			),
			array(
				'xx',
				'<rt><X>x</X><X>x</X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->rules->closeParent('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('X', 1, 0);
				}
			),
		);
	}
}