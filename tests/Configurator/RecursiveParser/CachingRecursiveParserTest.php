<?php

namespace s9e\TextFormatter\Tests\Configurator\RecursiveParser;

use s9e\TextFormatter\Configurator\RecursiveParser\CachingRecursiveParser;
use s9e\TextFormatter\Configurator\RecursiveParser\MatcherInterface;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RecursiveParser\CachingRecursiveParser
*/
class CachingRecursiveParserTest extends Test
{
	/**
	* @testdox Returns cached result if available
	*/
	public function testCached()
	{
		$parser = new CachingRecursiveParser;
		$parser->setMatchers([new TestMatcher]);

		$this->assertEquals(
			[
				'groups' => [],
				'match'  => 'GetInvoked',
				'value'  => 0
			],
			$parser->parse('GetInvoked')
		);
		$this->assertEquals(
			[
				'groups' => [],
				'match'  => 'Invoke',
				'value'  => 1
			],
			$parser->parse('Invoke')
		);
		$this->assertEquals(
			[
				'groups' => [],
				'match'  => 'Invoke',
				'value'  => 1
			],
			$parser->parse('Invoke')
		);
	}

	/**
	* @testdox Resets cache when setMatchers() is called
	*/
	public function testResetCache()
	{
		$parser = new CachingRecursiveParser;
		for ($i = 0; $i < 2; ++$i)
		{
			$parser->setMatchers([new TestMatcher]);

			$this->assertEquals(
				[
					'groups' => [],
					'match'  => 'GetInvoked',
					'value'  => 0
				],
				$parser->parse('GetInvoked')
			);
			$this->assertEquals(
				[
					'groups' => [],
					'match'  => 'Invoke',
					'value'  => 1
				],
				$parser->parse('Invoke')
			);
		}
	}
}

class TestMatcher implements MatcherInterface
{
	public $invoked = 0;
	public function getMatchers(): array
	{
		return [
			'GetInvoked' => 'GetInvoked',
			'Invoke'     => 'Invoke'
		];
	}

	public function parseGetInvoked()
	{
		return $this->invoked;
	}

	public function parseInvoke()
	{
		return ++$this->invoked;
	}
}