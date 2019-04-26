<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Parser;
use s9e\TextFormatter\Plugins\ParserBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\ParserBase
*/
class ParserBaseTest extends Test
{
	/**
	* @testdox Has a default empty setUp() method
	*/
	public function testHasSetup()
	{
		$parser = new DummyParser;
		$plugin = new DummyAssertParser($parser, []);

		$plugin->assert($this);
	}

	/**
	* @testdox Constructor calls setUp()
	*/
	public function testSetup()
	{
		$parser = new DummyParser;
		$plugin = new DummyPluginParser($parser, []);

		$this->assertSame(1, $plugin->called);
	}

	/**
	* @testdox Constructor sets up $this->config and $this->parser before calling setUp()
	*/
	public function testPropsBeforeSetup()
	{
		$parser = new DummyParser;
		$plugin = new DummyPluginParser($parser, []);

		$this->assertTrue($plugin->configWasSet);
		$this->assertTrue($plugin->parserWasSet);
	}
}

class DummyParser extends Parser
{
	public function __construct()
	{
	}
}

class DummyPluginParser extends ParserBase
{
	public $called = 0;
	public $configWasSet;
	public $parserWasSet;

	protected function setUp(): void
	{
		++$this->called;
		$this->configWasSet = isset($this->config);
		$this->parserWasSet = isset($this->parser);
	}

	public function parse($text, array $matches) {}
}

class DummyAssertParser extends ParserBase
{
	public function assert(Test $test)
	{
		$test->assertTrue(is_callable([$this, 'setUp']));
	}

	public function parse($text, array $matches) {}
}