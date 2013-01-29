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
		];
	}
}