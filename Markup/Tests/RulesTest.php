<?php

namespace s9e\Toolkit\Markup;

include_once __DIR__ . '/../ConfigBuilder.php';
include_once __DIR__ . '/../Parser.php';

class RulesTest extends \PHPUnit_Framework_TestCase
{
	public function testRequireParent()
	{
		$text = '[*]list item';
		$xml  = $this->parser->parse($text);

		$this->assertNotContains('<LI>', $xml);
	}

	public function testCloseParent()
	{
		$text     = '[list][*]one[*]two[/list]';
		$expected =
		            '<rt><LIST><st>[list]</st><LI><st>[*]</st>one</LI><LI><st>[*]</st>two</LI><et>[/list]</et></LIST></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testDeny()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('b');
		$cb->addBBCode('denied');

		$cb->addBBCodeRule('b', 'deny', 'denied');

		$text     = '[b][denied][/denied][/b]';
		$expected = '<rt><B><st>[b]</st>[denied][/denied]<et>[/b]</et></B></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function testRequireAscendant()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('foo');
		$cb->addBBCode('bar');

		$cb->addBBCodeRule('bar', 'require_ascendant', 'foo');

		$text     = ' [bar/] [foo][bar][/bar][/foo]';
		$expected =
		            '<rt> [bar/] <FOO><st>[foo]</st><BAR><st>[bar]</st><et>[/bar]</et></BAR><et>[/foo]</et></FOO></rt>';
		$actual   = $cb->getParser()->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function setUp()
	{
		$cb = new ConfigBuilder;

		$cb->addBBCode('b');
		$cb->addBBCode('list');
		$cb->addBBCode('li');

		$cb->addBBCodeAlias('li', '*');

		$cb->addBBCodeRule('li', 'require_parent', 'list');
		$cb->addBBCodeRule('li', 'close_parent', 'li');

		$this->parser = new Parser($cb->getParserConfig());
	}
}