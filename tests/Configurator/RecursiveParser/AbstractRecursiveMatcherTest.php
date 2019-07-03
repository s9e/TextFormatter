<?php

namespace s9e\TextFormatter\Tests\Configurator\RecursiveParser;

use s9e\TextFormatter\Configurator\RecursiveParser\AbstractRecursiveMatcher;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\RecursiveParser\AbstractRecursiveMatcher
*/
class AbstractRecursiveMatcherTest extends Test
{
	/**
	* @testdox recurse($str) calls $parser->parse($str) and returns its "value" element
	*/
	public function testRecurse()
	{
		$parser = $this->createMock('s9e\\TextFormatter\\Configurator\\RecursiveParser');
		$parser->expects($this->once())
		       ->method('parse')
		       ->with('Foo')
		       ->will($this->returnValue(['value' => 'FooValue']));

		$matcher = new TestAbstractRecursiveMatcher($parser);
		$this->assertSame('FooValue', $matcher->parseFoo('Foo'));
	}
}

class TestAbstractRecursiveMatcher extends AbstractRecursiveMatcher
{
	public function getMatchers(): array
	{
		return [];
	}
	public function parseFoo($str)
	{
		return $this->recurse($str);
	}
}