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
	* @testdox Constructor calls setUp()
	*/
	public function testSetup()
	{
		$parser = new DummyParser;
		$plugin = new DummyPluginParser($parser, array());

		$this->assertSame(1, $plugin->called);
	}

	/**
	* @testdox Constructor sets up $this->config and $this->parser before calling setUp()
	*/
	public function testPropsBeforeSetup()
	{
		$parser = new DummyParser;
		$plugin = new DummyPluginParser($parser, array());

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

	public function setUp()
	{
		++$this->called;
		$this->configWasSet = isset($this->config);
		$this->parserWasSet = isset($this->parser);
	}

	public function parse($text, array $matches) {}
}