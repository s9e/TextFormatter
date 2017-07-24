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
		$parser = $this->getParser();

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
		$parser = $this->getParser();

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
			$this->getParser()->getLogger()
		);
	}

	/**
	* @testdox The logger is cleared before parsing a new text
	*/
	public function testLoggerIsClearedAtStart()
	{
		$parser = $this->getParser();
		$logger = $parser->getLogger();

		$logger->debug('debug');
		$parser->parse('');
		$this->assertEmpty($logger->get());
	}

	/**
	* @testdox getText() returns the last text that's been parsed
	*/
	public function testGetText()
	{
		$parser = $this->getParser();
		$text   = 'Hello world';

		$parser->parse($text);

		$this->assertSame($text, $parser->getText());
	}

	/**
	* @testdox getText() returns the text, normalized
	*/
	public function testGetTextNormalized()
	{
		$parser = $this->getParser();
		$text   = "Hello world\r\n";

		$parser->parse($text);

		$this->assertSame("Hello world\n", $parser->getText());
	}

	/**
	* @testdox parse() returns the intermediate representation
	*/
	public function testParse()
	{
		$parser = $this->getParser();

		$this->assertSame(
			'<t>Plain text</t>',
			$parser->parse('Plain text')
		);
	}

	/**
	* @testdox parse() can be called multiple times in succession
	*/
	public function testParseIsClean()
	{
		$parser = $this->getParser();

		$parser->parse('Foo');

		$this->assertSame(
			'<t>Plain text</t>',
			$parser->parse('Plain text')
		);
	}

	/**
	* @testdox parse() normalizes \r to \n
	*/
	public function testParseCR()
	{
		$parser = $this->getParser();

		$this->assertSame(
			"<t>Plain\ntext</t>",
			$parser->parse("Plain\rtext")
		);
	}

	/**
	* @testdox parse() normalizes \r\n to \n
	*/
	public function testParseCRLF()
	{
		$parser = $this->getParser();

		$this->assertSame(
			"<t>Plain\ntext</t>",
			$parser->parse("Plain\r\ntext")
		);
	}

	/**
	* @testdox parse() removes control characters that aren't allowed in XML
	*/
	public function testParseFiltersLowAscii()
	{
		$parser = $this->getParser();

		$this->assertSame(
			"<t>Plain\t\n\n text</t>",
			$parser->parse('Plain' . implode('', array_map('chr', range(0, 0x20))) . 'text')
		);
	}

	/**
	* @testdox disableTag('FOO') disables tag 'FOO'
	*/
	public function testDisableTag()
	{
		$this->configurator->tags->add('FOO');
		$parser = $this->getParser();

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertTrue(empty($tagsConfig['FOO']['isDisabled']));

		$parser->disableTag('FOO');

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertFalse(empty($tagsConfig['FOO']['isDisabled']));
	}

	/**
	* @testdox disableTag() does not have side-effects due to references
	*/
	public function testDisableTagReference()
	{
		$configurator = new Configurator;
		$configurator->tags->add('FOO');
		$configurator->tags->add('BAR');

		extract($configurator->finalize());

		$parser->disableTag('FOO');

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertFalse(empty($tagsConfig['FOO']['isDisabled']));
		$this->assertTrue(empty($tagsConfig['BAR']['isDisabled']));
	}

	/**
	* @testdox enableTag('FOO') re-enables tag 'FOO'
	*/
	public function testEnableTag()
	{
		$this->configurator->tags->add('FOO');
		$parser = $this->getParser();

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
		$parser = $this->getParser();

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

	/**
	* @testdox setTagLimit('X', 7) set tag X's tagLimit to 7 if it exists
	*/
	public function testSetTagLimit()
	{
		$this->configurator->tags->add('X')->tagLimit = 3;

		$parser = $this->getParser();

		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				for ($i = 0; $i < 8; ++$i)
				{
					$parser->addSelfClosingTag('X', $i, 1);
				}
			}
		);

		$this->assertSame(
			'<r><X>0</X><X>1</X><X>2</X>34567</r>',
			$parser->parse('01234567')
		);

		$parser->setTagLimit('X', 7);

		$this->assertSame(
			'<r><X>0</X><X>1</X><X>2</X><X>3</X><X>4</X><X>5</X><X>6</X>7</r>',
			$parser->parse('01234567')
		);
	}

	/**
	* @testdox setTagLimit() does not have side-effects due to references
	*/
	public function testSetTagLimitReference()
	{
		$configurator = new Configurator;
		$configurator->tags->add('FOO');
		$configurator->tags->add('BAR');

		extract($configurator->finalize());

		$parser->setTagLimit('FOO', 123);
		$parser->setTagLimit('BAR', 456);

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertSame(123, $tagsConfig['FOO']['tagLimit']);
		$this->assertSame(456, $tagsConfig['BAR']['tagLimit']);
	}

	/**
	* @testdox setNestingLimit('X', 7) set tag X's tagLimit to 7 if it exists
	*/
	public function testSetNestingLimit()
	{
		$this->configurator->tags->add('X')->nestingLimit = 3;

		$parser = $this->getParser();

		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				for ($i = 0; $i < 8; ++$i)
				{
					$parser->addTagPair('X', 0, 0, 1, 0);
				}
			}
		);

		$this->assertSame(
			'<r><X><X><X> </X></X></X></r>',
			$parser->parse(' ')
		);

		$parser->setNestingLimit('X', 7);

		$this->assertSame(
			'<r><X><X><X><X><X><X><X> </X></X></X></X></X></X></X></r>',
			$parser->parse(' ')
		);
	}

	/**
	* @testdox setNestingLimit() does not have side-effects due to references
	*/
	public function testSetNestingLimitReference()
	{
		$configurator = new Configurator;
		$configurator->tags->add('FOO');
		$configurator->tags->add('BAR');

		extract($configurator->finalize());

		$parser->setNestingLimit('FOO', 123);
		$parser->setNestingLimit('BAR', 456);

		$tagsConfig = $this->readAttribute($parser, 'tagsConfig');
		$this->assertSame(123, $tagsConfig['FOO']['nestingLimit']);
		$this->assertSame(456, $tagsConfig['BAR']['nestingLimit']);
	}

	/**
	* @testdox Characters outside Unicode's BMP are encoded
	*/
	public function testUnicodeSMP()
	{
		$text = '😀';
		$xml  = $this->getParser()->parse($text);
		$this->assertSame('<t>&#128512;</t>', $xml);
	}

	/**
	* @testdox Attribute preprocessors are properly run with default config
	*/
	public function testAttributePreprocessors()
	{
		$configurator = new Configurator;
		$tag = $configurator->tags->add('X');
		$tag->attributePreprocessors->add('foo', '/(?<bar>\\d+)/');
		$tag->attributes->add('bar');

		extract($configurator->finalize());

		$parser->registerParser(
			'foo',
			function ($text) use ($parser)
			{
				$parser->addSelfClosingTag('X', 0, 0)->setAttribute('foo', '123');
			}
		);

		$this->assertSame(
			'<r><X bar="123"/></r>',
			$parser->parse('')
		);
	}
}