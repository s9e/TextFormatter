<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class BasicTest extends \PHPUnit_Framework_TestCase
{
	public function testPlainText()
	{
		$text     = 'This is some plain text.';
		$expected = '<pt>This is some plain text.</pt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testPlainTextResultIsReversible()
	{
		$text   = 'This is some plain text.';
		$xml    = $this->parser->parse($text);

		$actual = html_entity_decode(strip_tags($xml));

		$this->assertSame($text, $actual);
	}

	public function testRichText()
	{
		$text     = 'This is some [b]bold[/b] text.';
		$expected = '<rt>This is some <B><st>[b]</st>bold<et>[/b]</et></B> text.</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testRichTextResultIsReversible()
	{
		$text   = "This is some [b]bold[/b] text with special \"'& \xE2\x99\xA5<characters>\r\n"
		        . '...and line breaks too.';
		$xml    = $this->parser->parse($text);

		$actual = html_entity_decode(strip_tags($xml));

		$this->assertSame($text, $actual);
	}

	public function testNestingLimitIsRespected()
	{
		$text     = 'This is some [b][b]bold[/b] text.';
		$expected = '<rt>This is some <B><st>[b]</st>[b]bold<et>[/b]</et></B> text.</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	/**
	* @depends testNestingLimitIsRespected
	*/
	public function testBBCodeSuffix()
	{
		$text     = 'This is some [b:123][b]bold[/b][/b:123] text.';
		$expected = '<rt>This is some <B><st>[b:123]</st>[b]bold[/b]<et>[/b:123]</et></B> text.</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testEmoticon()
	{
		$text     = 'test :) :)';
		$expected = '<rt>test <E>:)</E> <E>:)</E></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testBBCodesFromTokenizersAreUppercasedIfNeeded()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCode('b');

		$config = $cb->getParserConfig();

		$config['passes']['custom'] = array(
			'parser' => function()
			{
				return array(
					'tags' => array(
						array(
							'pos'  => 0,
							'len'  => 0,
							'type' => Parser::TAG_OPEN,
							'name' => 'b'
						),
						array(
							'pos'  => 3,
							'len'  => 0,
							'type' => Parser::TAG_CLOSE,
							'name' => 'B'
						)
					)
				);
			}
		);

		$parser = new Parser($config);

		$expected = '<rt><B>foo</B></rt>';
		$actual   = $parser->parse('foo');

		$this->assertSame($expected, $actual);
	}

	public function testUnknownBBCodesAreIgnored()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCode('b');
		$cb->addBBCode('i');

		/**
		* It is possible that an application would selectively disable BBCodes by altering the
		* config rather than regenerate a whole new one. We make sure stuff doesn't go haywire
		*/
		$config = $cb->getParserConfig();
		unset($config['passes']['BBCode']['aliases']['I']);
		unset($config['passes']['BBCode']['bbcodes']['I']);

		$parser = new Parser($config);

		$text     = '[i]foo[/i]';
		$expected = '<pt>[i]foo[/i]</pt>';
		$actual   = $parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testUnknownBBCodesFromCustomPassesAreIgnored()
	{
		$cb = new ConfigBuilder;
		$cb->addBBCode('b');

		$config = $cb->getParserConfig();

		$config['passes']['custom'] = array(
			'parser' => function()
			{
				return array(
					'tags' => array(
						array(
							'pos'  => 0,
							'len'  => 0,
							'type' => Parser::TAG_OPEN,
							'name' => 'Z'
						),
						array(
							'pos'  => 3,
							'len'  => 0,
							'type' => Parser::TAG_CLOSE,
							'name' => 'Z'
						)
					)
				);
			}
		);

		$parser = new Parser($config);

		$expected = '<pt>foo</pt>';
		$actual   = $parser->parse('foo');

		$this->assertSame($expected, $actual);
	}

	public function testAutolink()
	{
		$text     = 'Go to http://www.example.com for more';
		$expected = '<rt>Go to <A href="http://www.example.com">http://www.example.com</A> for more</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testCensor()
	{
		$text     = 'You dirty apple';
		$expected = '<rt>You dirty <C>apple</C></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testCensorWithReplacement()
	{
		$text     = 'You dirty banana';
		$expected = '<rt>You dirty <C with="pear">banana</C></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function setUp()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('b', array('nesting_limit' => 1));

		$cb->addBBCode('a');
		$cb->addBBCodeParam('a', 'href', 'url', true);
		$cb->setAutolinkOption('bbcode', 'a');
		$cb->setAutolinkOption('param', 'href');

		$cb->addBBCode('x');
		$cb->addBBCodeParam('x', 'foo', 'text', false);

		$cb->addEmoticon(':)', '<img src="happy.png" alt=":)" />');

		$cb->addCensor('apple');
		$cb->addCensor('banana', 'pear');

		$this->parser = $cb->getParser();
	}
}