<?php

namespace s9e\toolkit\markup;

include_once __DIR__ . '/../config_builder.php';
include_once __DIR__ . '/../parser.php';

class testRules extends \PHPUnit_Framework_TestCase
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
		$expected = '<rt><LIST><st>[list]</st><LI><st>[*]</st>one</LI><LI><st>[*]</st>two</LI><et>[/list]</et></LIST></rt>';
		$actual   = $this->parser->parse($text);

		$this->assertSame($expected, $actual);
	}

	public function setUp()
	{
		$cb = new config_builder;

		$cb->addBBCode('b');
		$cb->addBBCode('list');
		$cb->addBBCode('li');

		$cb->addBBCodeAlias('li', '*');

		$cb->addBBCodeRule('li', 'require_parent', 'list');
		$cb->addBBCodeRule('li', 'close_parent', 'li');

		$this->parser = new parser($cb->getParserConfig());
	}
}