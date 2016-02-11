<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
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
				'xy',
				'<r><X>x<Y>y</Y></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->closeParent('Y');
					$configurator->tags->add('Y')->rules->closeParent('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('Y', 1, 0);
				}
			],
			[
				'xyx',
				'<r><X>x</X><Y>y</Y>x</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y')->rules->closeParent('X');
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
				'<r><X>x</X><Y>y</Y>x</r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y')->rules->closeAncestor('X');
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
				'<r><X>x<X>x</X></X><Y>y</Y></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y')->rules->closeAncestor('X');
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
				'<r><X>x<Y>y</Y></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->closeAncestor('Y');
					$configurator->tags->add('Y')->rules->closeAncestor('Y');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('Y', 1, 0);
				}
			],
			[
				'xx',
				'<t>xx</t>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->requireAncestor('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('X', 1, 0);
				}
			],
			[
				'xy',
				'<r><X>x<Y>y</Y></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y')->rules->requireAncestor('X');
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 0);
					$parser->addStartTag('Y', 1, 0);
				}
			],
			[
				'[b]..[div]..[/div]',
				'<r><B><s>[b]</s>..</B><DIV><s>[div]</s><B>..</B><e>[/div]</e></DIV></r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('DIV')->rules->fosterParent('B');
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
				'<r><B><s>[b]</s>..</B><DIV><s>[div]</s><B>..</B><e>[/div]</e></DIV><B>..<e>[/b]</e></B></r>',
				function ($configurator)
				{
					$configurator->tags->add('B')->rules->autoReopen();
					$configurator->tags->add('DIV')->rules->fosterParent('B');
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
				'<r><B><s>[b]</s>..</B><DIV><s>[div]</s><B>.<e>[/b]</e></B>.<e>[/div]</e></DIV>..[/b]</r>',
				function ($configurator)
				{
					$configurator->tags->add('B')->rules->autoReopen();
					$configurator->tags->add('DIV')->rules->fosterParent('B');
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
				'<r><DIV><s>[div]</s></DIV><DIV><s>[div]</s>..<e>[/div]</e></DIV>[/div]</r>',
				function ($configurator)
				{
					$configurator->tags->add('DIV')->rules->fosterParent('DIV');
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
				'<r><X><s>[X]</s></X><Y><s>[Y]</s>..</Y></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->fosterParent('Y');
					$configurator->tags->add('Y')->rules->fosterParent('X');
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
				'<r><X><s>[X]</s></X><Y><s>[Y]</s></Y><X>..</X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->fosterParent('Y');
					$configurator->tags->add('Y')->rules->fosterParent('X');
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
				'<r><X><s>[x]</s>.</X><Z>[z/]</Z><X>.<e>[/x]</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Z')->rules->fosterParent('X');
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
				'<r><X><s>[x]</s><Y><s>[y]</s>.<Z>[z/]</Z>.<e>[/y]</e></Y><e>[/x]</e></X></r>',
				function ($configurator)
				{
					$configurator->tags->add('X');
					$configurator->tags->add('Y');
					$configurator->tags->add('Z')->rules->fosterParent('X');
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
			[
				'XXX',
				'<r><B><s>X</s><B><s>X</s></B></B><Q><B><B>X</B></B></Q></r>',
				function ($configurator)
				{
					$configurator->tags->add('B');
					$configurator->tags->add('Q')->rules->fosterParent('B');
				},
				function ($parser)
				{
					$parser->addStartTag('B', 0, 1);
					$parser->addStartTag('B', 1, 1);
					$parser->addStartTag('Q', 2, 0)->setSortPriority(-10);
				}
			],
			[
				"[code]\n...[/code]",
				"<r><X><s>[code]</s><i>\n</i>...<e>[/code]</e></X></r>",
				function ($configurator)
				{
					$configurator->tags->add('X')->rules->trimFirstLine();
				},
				function ($parser)
				{
					$parser->addStartTag('X', 0, 6);
					$parser->addEndTag('X', 10, 7);
				}
			],
		];
	}
}