<?php

namespace s9e\TextFormatter\Tests\Plugins\Keywords;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\Keywords\Parser;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsRunner;
use s9e\TextFormatter\Tests\Plugins\ParsingTestsJavaScriptRunner;
use s9e\TextFormatter\Tests\Plugins\RenderingTestsRunner;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Keywords\Parser
*/
class ParserTest extends Test
{
	use ParsingTestsRunner;
	use ParsingTestsJavaScriptRunner;
//	use RenderingTestsRunner;

	public static function getParsingTests()
	{
		return [
			[
				'foo bar baz',
				'<r><KEYWORD value="foo">foo</KEYWORD> bar baz</r>',
				[],
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			],
			[
				'foo bar baz',
				'<r><FOO value="foo">foo</FOO> bar baz</r>',
				['tagName' => 'FOO'],
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			],
			[
				'foo bar baz',
				'<r><KEYWORD foo="foo">foo</KEYWORD> bar baz</r>',
				['attrName' => 'foo'],
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			],
			[
				'foo foo foo',
				'<r><KEYWORD value="foo">foo</KEYWORD> <KEYWORD value="foo">foo</KEYWORD> <KEYWORD value="foo">foo</KEYWORD></r>',
				[],
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
				}
			],
			[
				'foo foo foo',
				'<r><KEYWORD value="foo">foo</KEYWORD> foo foo</r>',
				[],
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
					$configurator->Keywords->onlyFirst = true;
				}
			],
			[
				'foo foo bar bar',
				'<r><KEYWORD value="foo">foo</KEYWORD> foo <KEYWORD value="bar">bar</KEYWORD> bar</r>',
				[],
				function ($configurator)
				{
					$configurator->Keywords->add('foo');
					$configurator->Keywords->add('bar');
					$configurator->Keywords->onlyFirst = true;
				}
			],
		];
	}
}