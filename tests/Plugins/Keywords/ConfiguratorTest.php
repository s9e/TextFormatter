<?php

namespace s9e\TextFormatter\Tests\Plugins\Keywords;

use s9e\TextFormatter\Plugins\Keywords\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Keywords\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates a "KEYWORD" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->Keywords;
		$this->assertTrue($this->configurator->tags->exists('KEYWORD'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Keywords', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Keywords', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['KEYWORD']->attributes->exists('bar'));
	}

	/**
	* @testdox asConfig() returns NULL if no keyword was added
	*/
	public function testConfigFalse()
	{
		$this->assertNull($this->configurator->Keywords->asConfig());
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$this->configurator->Keywords->add('foo');
		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('KEYWORD', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('Keywords', ['attrName' => 'bar']);
		$plugin->add('foo');

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox The config array contains an array of regexps
	*/
	public function testConfigRegexps()
	{
		$this->configurator->Keywords->add('foo');

		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayHasKey('regexps', $config);
		$this->assertEquals(['/\\bfoo\\b/S'], array_map('strval', $config['regexps']));
	}

	/**
	* @testdox Keywords are split in groups to generate regexps smaller than ~32KB
	*/
	public function testConfigRegexpsHuge()
	{
		// 62 chars * 1000 = 68200 bytes that should be split in 3 regexps
		$chars = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
		foreach ($chars as $char)
		{
			$this->configurator->Keywords->add(str_repeat($char, 1100));
		}

		$config = $this->configurator->Keywords->asConfig();
		$this->assertArrayHasKey('regexps', $config);
		$this->assertGreaterThan(2, count($config['regexps']));
	}

	/**
	* @testdox Regexps are case-insensitive if $plugin->caseSensitive is false
	*/
	public function testCaseInsensitive()
	{
		$this->configurator->Keywords->add('foo');
		$this->configurator->Keywords->caseSensitive = false;

		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayHasKey('regexps', $config);
		$this->assertEquals(['/\\bfoo\\b/Si'], array_map('strval', $config['regexps']));
	}

	/**
	* @testdox Regexps that contain a non-ASCII character use Unicode mode
	*/
	public function testUnicode()
	{
		$this->configurator->Keywords->add('föo');

		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayHasKey('regexps', $config);
		$this->assertEquals(['/\\bföo\\b/Su'], array_map('strval', $config['regexps']));
	}

	/**
	* @testdox asConfig() does not return an entry for onlyFirst by default
	*/
	public function testOnlyFirstDefault()
	{
		$this->configurator->Keywords->add('foo');

		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayNotHasKey('onlyFirst', $config);
	}

	/**
	* @testdox asConfig() has an entry for onlyFirst if it's true
	*/
	public function testOnlyFirstTrue()
	{
		$this->configurator->Keywords->add('foo');
		$this->configurator->Keywords->onlyFirst = true;

		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayHasKey('onlyFirst', $config);
		$this->assertTrue($config['onlyFirst']);
	}

	/**
	* @testdox asConfig() does not return an entry for onlyFirst if it's false
	*/
	public function testOnlyFirstFalse()
	{
		$this->configurator->Keywords->add('foo');
		$this->configurator->Keywords->onlyFirst = false;

		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayNotHasKey('onlyFirst', $config);
	}
}