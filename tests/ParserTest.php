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
		$configurator = new Configurator;
		$parser       = $configurator->getParser();

		$this->assertStringStartsWith(
			'O:24:"s9e\\TextFormatter\\Parser"',
			serialize($parser)
		);
	}

	/**
	* @testdox Parser can be unserialized
	*/
	public function testUnserialize()
	{
		$configurator = new Configurator;
		$parser       = $configurator->getParser();

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
	* @testdox The logger is cleared before parsing a new text
	*/
	public function testLoggerIsClearedAtStart()
	{
		$parser = $this->configurator->getParser();
		$logger = $parser->getLogger();

		$logger->debug('debug');
		$parser->parse('');
		$this->assertEmpty($logger->get());
	}

	/**
	* @testdox parse() returns the intermediate representation
	*/
	public function testParse()
	{
		$configurator = new Configurator;
		$parser       = $configurator->getParser();

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
		$configurator = new Configurator;
		$parser       = $configurator->getParser();

		$parser->parse('Foo');

		$this->assertSame(
			'<pt>Plain text</pt>',
			$parser->parse('Plain text')
		);
	}

	/**
	* @testdox parse() normalizes \r to \n
	*/
	public function testParseCR()
	{
		$configurator = new Configurator;
		$configurator->rootRules->noBrChild();
		$parser       = $configurator->getParser();

		$this->assertSame(
			"<pt>Plain\ntext</pt>",
			$parser->parse("Plain\rtext")
		);
	}

	/**
	* @testdox parse() normalizes \r\n to \n
	*/
	public function testParseCRLF()
	{
		$configurator = new Configurator;
		$configurator->rootRules->noBrChild();
		$parser       = $configurator->getParser();

		$this->assertSame(
			"<pt>Plain\ntext</pt>",
			$parser->parse("Plain\r\ntext")
		);
	}

	/**
	* @testdox parse() removes control characters that aren't allowed in XML
	*/
	public function testParseFiltersLowAscii()
	{
		$configurator = new Configurator;
		$configurator->rootRules->noBrChild();
		$parser       = $configurator->getParser();

		$this->assertSame(
			"<pt>Plain\t\n\n text</pt>",
			$parser->parse('Plain' . implode('', array_map('chr', range(0, 0x20))) . 'text')
		);
	}

	/**
	* @testdox disableTag('FOO') disables tag 'FOO'
	*/
	public function testDisableTag()
	{
		$configurator = new Configurator;
		$configurator->tags->add('FOO');
		$parser       = $configurator->getParser();

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertTrue(empty($tagsConfig['FOO']['isDisabled']));

		$parser->disableTag('FOO');

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertFalse(empty($tagsConfig['FOO']['isDisabled']));
	}

	/**
	* @testdox enableTag('FOO') re-enables tag 'FOO'
	*/
	public function testEnableTag()
	{
		$configurator = new Configurator;
		$configurator->tags->add('FOO');
		$parser       = $configurator->getParser();

		$parser->disableTag('FOO');

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertFalse(empty($tagsConfig['FOO']['isDisabled']));

		$parser->enableTag('FOO');

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertTrue(empty($tagsConfig['FOO']['isDisabled']));
	}

	/**
	* @testdox parse() throws an exception if the parser is reset during its execution
	* @expectedException RuntimeException
	* @expectedExceptionMessage The parser has been reset during execution
	*/
	public function testResetException()
	{
		$configurator = new Configurator;
		$parser       = $configurator->getParser();

		$parser->registerParser(
			'Test',
			function ($text) use ($parser)
			{
				if ($text === '...')
				{
					$parser->parse('___');
				}
			}
		);

		$parser->parse('...');
	}
}