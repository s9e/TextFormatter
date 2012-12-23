<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser
*/
class ParserTest extends Test
{
	/**
	* @testdox Parser is serializable
	*/
	public function testSerialize()
	{
		$parser = $this->configurator->getParser();

		$this->assertStringStartsWith(
			'C:24:"s9e\\TextFormatter\\Parser"',
			serialize($parser)
		);
	}

	/**
	* @testdox Parser can be unserialized
	*/
	public function testUnserialize()
	{
		$parser = $this->configurator->getParser();

		$this->assertEquals(
			$parser,
			unserialize(serialize($parser))
		);
	}

	/**
	* @testdox getLogger() returns an instance of Logger
	*/
	public function testGetLogger()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Parser\\Logger',
			$this->configurator->getParser()->getLogger()
		);
	}

	/**
	* @testdox parse() returns the intermediate representation
	*/
	public function testParse()
	{
		$parser = $this->configurator->getParser();

		$this->assertSame(
			'<pt>Plain text</pt>',
			$parser->parse('Plain text')
		);
	}

	/**
	* @testdox parse() can be called multiple times in succession
	*/
	public function testParseIsClean()
	{
		$parser = $this->configurator->getParser();

		$parser->parse('Foo');

		$this->assertSame(
			'<pt>Plain text</pt>',
			$parser->parse('Plain text')
		);
	}
}