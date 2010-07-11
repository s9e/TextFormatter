<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testBasic extends \PHPUnit_Framework_TestCase
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
		$expected = '<rt>test <E code=":)">:)</E> <E code=":)">:)</E></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testAutolink()
	{
		$text     = 'Go to http://www.example.com for more';
		$expected = '<rt>Go to <A href="http://www.example.com">http://www.example.com</A> for more</rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testBBCodesInsideParamsAreIgnored()
	{
		$text     = '[x foo="[b]bar[/b]" /]';
		$expected = '<rt><X foo="[b]bar[/b]">[x foo=&quot;[b]bar[/b]&quot; /]</X></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->addBBCode('b', array('nesting_limit' => 1));
		$cb->addBBCode('e', array(
			'default_param'    => 'code',
			'content_as_param' => true
		));
		$cb->addBBCodeParam('e', 'code', 'text', true);

		$cb->setEmoticonOption('bbcode', 'e');
		$cb->setEmoticonOption('param', 'code');

		$cb->addEmoticon(':)');

		$cb->addBBCode('a');
		$cb->addBBCodeParam('a', 'href', 'url', true);
		$cb->setAutolinkOption('bbcode', 'a');
		$cb->setAutolinkOption('param', 'href');

		$cb->addBBCode('x');
		$cb->addBBCodeParam('x', 'foo', 'text', false);

		$this->parser = new parser($cb->getParserConfig());
	}
}