<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\RulesHandling;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\RulesHandling
*/
class RulesHandlingTest extends Test
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
			],
			[
				'xy',
				'<rt><X>x<Y>y</Y></X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->rules->closeParent('Y');
					$constructor->tags->add('Y')->rules->closeParent('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('Y', 1, 0);
				}
			],
			[
				'xyx',
				'<rt><X>x</X><Y>y</Y>x</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y')->rules->closeParent('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0)
					       ->pairWith($parser->addEndTag('X', 3, 0));
					$parser->addSelfClosingTag('Y', 1, 1);
				}
			],
			[
				'xyx',
				'<rt><X>x</X><Y>y</Y>x</rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y')->rules->closeAncestor('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0)
					       ->pairWith($parser->addEndTag('X', 3, 0));
					$parser->addSelfClosingTag('Y', 1, 1);
				}
			],
			[
				'xxy',
				'<rt><X>x<X>x</X></X><Y>y</Y></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y')->rules->closeAncestor('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('X', 1, 0);
					$parser->addSelfClosingTag('Y', 2, 1);
				}
			],
			[
				'xy',
				'<rt><X>x<Y>y</Y></X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->rules->closeAncestor('Y');
					$constructor->tags->add('Y')->rules->closeAncestor('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('Y', 1, 0);
				}
			],
			[
				'xx',
				'<pt>xx</pt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->rules->requireAncestor('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('X', 1, 0);
				}
			],
			[
				'xy',
				'<rt><X>x<Y>y</Y></X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y')->rules->requireAncestor('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('Y', 1, 0);
				}
			],
			[
				'[b]..[div]..[/div]',
				'<rt><B><st>[b]</st>..</B><DIV><st>[div]</st><B>..</B><et>[/div]</et></DIV></rt>',
				function ($constructor)
				{
					$constructor->tags->add('B');
					$constructor->tags->add('DIV')->rules->fosterParent('B');
				},
				function ($parser)
				{
					$parser->addStartTag('B', 0, 3);
					$parser->addStartTag('DIV', 5, 5);
					$parser->addEndTag('DIV', 12, 6);
				}
			],
			[
				'[b]..[div]..[/div]..[/b]',
				'<rt><B><st>[b]</st>..</B><DIV><st>[div]</st><B>..</B><et>[/div]</et></DIV><B>..<et>[/b]</et></B></rt>',
				function ($constructor)
				{
					$constructor->tags->add('B')->rules->autoReopen();
					$constructor->tags->add('DIV')->rules->fosterParent('B');
				},
				function ($parser)
				{
					$parser->addStartTag('B', 0, 3);
					$parser->addStartTag('DIV', 5, 5);
					$parser->addEndTag('DIV', 12, 6);
					$parser->addEndTag('B', 20, 4);
				}
			],
			[
				'[b]..[div].[/b].[/div]..[/b]',
				'<rt><B><st>[b]</st>..</B><DIV><st>[div]</st><B>.<et>[/b]</et></B>.<et>[/div]</et></DIV>..[/b]</rt>',
				function ($constructor)
				{
					$constructor->tags->add('B')->rules->autoReopen();
					$constructor->tags->add('DIV')->rules->fosterParent('B');
				},
				function ($parser)
				{
					$parser->addStartTag('B', 0, 3);
					$parser->addStartTag('DIV', 5, 5);
					$parser->addEndTag('B', 11, 4);
					$parser->addEndTag('DIV', 16, 6);
					$parser->addEndTag('B', 24, 4);
				}
			],
			[
				'[div][div]..[/div][/div]',
				'<rt><DIV><st>[div]</st></DIV><DIV><st>[div]</st>..<et>[/div]</et></DIV>[/div]</rt>',
				function ($constructor)
				{
					$constructor->tags->add('DIV')->rules->fosterParent('DIV');
				},
				function ($parser)
				{
					$parser->addStartTag('DIV', 0, 5);
					$parser->addStartTag('DIV', 5, 5);
					$parser->addEndTag('DIV', 12, 6);
					$parser->addEndTag('DIV', 18, 6);
				}
			],
			[
				'[X][Y]..',
				'<rt><X><st>[X]</st></X><Y><st>[Y]</st>..</Y></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->rules->fosterParent('Y');
					$constructor->tags->add('Y')->rules->fosterParent('X');
				},
				function ($parser)
				{
					$parser->maxFixingCost = 0;
					$parser->addStartTag('X', 0, 3);
					$parser->addStartTag('Y', 3, 3);
				}
			],
			[
				'[X][Y]..',
				'<rt><X><st>[X]</st></X><Y><st>[Y]</st></Y><X>..</X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X')->rules->fosterParent('Y');
					$constructor->tags->add('Y')->rules->fosterParent('X');
				},
				function ($parser)
				{
					// Two tags close each other. The winner depends on the number of times the loop
					// is allowed to run
					$parser->maxFixingCost = 3;
					$parser->addStartTag('X', 0, 3);
					$parser->addStartTag('Y', 3, 3);
				}
			],
			[
				'[x].[z/].[/x]',
				'<rt><X><st>[x]</st>.</X><Z>[z/]</Z><X>.<et>[/x]</et></X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Z')->rules->fosterParent('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 3);
					$parser->addSelfClosingTag('Z', 4, 4);
					$parser->addEndTag('X', 9, 4);
				}
			],
			[
				'[x][y].[z/].[/y][/x]',
				'<rt><X><st>[x]</st><Y><st>[y]</st>.<Z>[z/]</Z>.<et>[/y]</et></Y><et>[/x]</et></X></rt>',
				function ($constructor)
				{
					$constructor->tags->add('X');
					$constructor->tags->add('Y');
					$constructor->tags->add('Z')->rules->fosterParent('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 3);
					$parser->addStartTag('Y', 3, 3);
					$parser->addSelfClosingTag('Z', 7, 4);
					$parser->addEndTag('Y', 12, 4);
					$parser->addEndTag('X', 16, 4);
				}
			],
		];
	}
}