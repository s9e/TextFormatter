<?php

namespace s9e\TextFormatter\Tests\Configurator;

use s9e\TextFormatter\Configurator\RecursiveParser;
use s9e\TextFormatter\Configurator\RecursiveParser\MatcherInterface;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RecursiveParser
*/
class RecursiveParserTest extends Test
{
	/**
	* @testdox Throws an exception if the input cannot be matched
	*/
	public function testException()
	{
		$parser = new RecursiveParser;
		$parser->setMatchers([new TestMatcher]);

		$this->expectException('RuntimeException');
		$this->expectExceptionMessage("Cannot parse 'XXX'");

		$parser->parse('XXX');
	}

	/**
	* @testdox Works
	* @dataProvider getParseTests
	*/
	public function test($str, $expected, $restrict = '')
	{
		$parser = new RecursiveParser;
		$parser->setMatchers([new TestMatcher]);

		$this->assertEquals($expected, $parser->parse($str, $restrict));
	}

	public function getParseTests()
	{
		return [
			[
				'Foo',
				[
					'match'  => 'Foo',
					'groups' => ['X', 'Y'],
					'value'  => "parseFoo('Foo')"
				]
			],
			[
				'FooBar',
				[
					'match'  => 'FooBar',
					'groups' => ['Z'],
					'value'  => "callback('Foo', 'Bar')"
				]
			],
			[
				'FooBar',
				[
					'match'  => 'NotFooBar',
					'groups' => [],
					'value'  => "parseNotFooBar('FooBar')"
				],
				'NotFooBar'
			],
		];
	}
}

class TestMatcher implements MatcherInterface
{
	public function getMatchers(): array
	{
		return [
			'X:Y:Foo'   => '(Foo)',
			'Z:FooBar'  => [
				'regexp'   => '(Foo)(Bar)',
				'order'    => -1,
				'callback' => [$this, 'callback']
			],
			'NotFooBar' => '(FooBar)'
		];
	}

	public function callback($str)
	{
		return $this->format(__METHOD__, func_get_args());
	}

	public function parseFoo($str)
	{
		return $this->format(__METHOD__, func_get_args());
	}

	public function parseNotFooBar($str)
	{
		return $this->format(__METHOD__, func_get_args());
	}

	protected function format($methodName, $args)
	{
		$methodName = str_replace(__CLASS__ . '::', '', $methodName);
		$args = array_map(
			function ($arg)
			{
				return var_export($arg, true);
			},
			$args
		);

		return $methodName . '(' . implode(', ', $args) . ')';
	}
}