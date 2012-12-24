<?php

namespace s9e\TextFormatter\Tests\Parser;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Parser\OutputHandling;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Parser\OutputHandling
*/
class OutputHandlingTest extends Test
{
	/**
	* @testdox Correctly outputs plain text
	*/
	public function testPlainText()
	{
		$parser = $this->configurator->getParser();

		$this->assertSame(
			'<pt>Plain text</pt>',
			$parser->parse('Plain text')
		);
	}

	/**
	* @testdox Correctly outputs plain text with line breaks
	*/
	public function testPlainTextMultiline()
	{
		$parser = $this->configurator->getParser();

		$this->assertSame(
			"<pt>Plain<br/>\ntext</pt>",
			$parser->parse("Plain\ntext")
		);
	}

	/**
	* @testdox Correctly outputs one zero-width self-closing tag at the start of the text
	*/
	public function testSelfClosingStart()
	{
		$this->configurator->tags->add('X');

		$parser = $this->configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addSelfClosingTag('X', 0, 0);
			}
		);

		$this->assertSame(
			'<rt><X/>foo bar</rt>',
			$parser->parse('foo bar')
		);
	}

	/**
	* @testdox Correctly outputs one zero-width self-closing tag in the middle of the text
	*/
	public function testSelfClosingMiddle()
	{
		$this->configurator->tags->add('X');

		$parser = $this->configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addSelfClosingTag('X', 3, 0);
			}
		);

		$this->assertSame(
			'<rt>foo<X/> bar</rt>',
			$parser->parse('foo bar')
		);
	}

	/**
	* @testdox Correctly outputs one zero-width self-closing tag at the end of the text
	*/
	public function testSelfClosingEnd()
	{
		$this->configurator->tags->add('X');

		$parser = $this->configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addSelfClosingTag('X', 7, 0);
			}
		);

		$this->assertSame(
			'<rt>foo bar<X/></rt>',
			$parser->parse('foo bar')
		);
	}

	/**
	* @testdox Correctly outputs a self-closing tag that consumes text
	*/
	public function testSelfClosingConsuming()
	{
		$this->configurator->tags->add('X');

		$parser = $this->configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addSelfClosingTag('X', 0, 3);
			}
		);

		$this->assertSame(
			'<rt><X>foo</X> bar</rt>',
			$parser->parse('foo bar')
		);
	}

	/**
	* @testdox Correctly outputs ignore tags
	*/
	public function testIgnore()
	{
		$parser = $this->configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addIgnoreTag(3, 1);
			}
		);

		$this->assertSame(
			'<rt>foo<i> </i>bar</rt>',
			$parser->parse('foo bar')
		);
	}

	/**
	* @testdox Correctly outputs br tags
	*/
	public function testBr()
	{
		$parser = $this->configurator->getParser();
		$parser->registerParser(
			'Test',
			function () use ($parser)
			{
				$parser->addBrTag(3);
			}
		);

		$this->assertSame(
			'<rt>foo<br/> bar</rt>',
			$parser->parse('foo bar')
		);
	}
}