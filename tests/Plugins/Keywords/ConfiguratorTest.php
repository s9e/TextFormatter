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
	* @testdox asConfig() returns FALSE if no keyword was added
	*/
	public function testConfigFalse()
	{
		$this->assertFalse($this->configurator->Keywords->asConfig());
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
		$this->assertEquals(['/\bfoo\b/S'], array_map('strval', $config['regexps']));
	}

	/**
	* @testdox Keywords are split in groups to generate regexps smaller than ~32KB
	*/
	public function testConfigRegexpsHuge()
	{
		for ($i = 0; $i < 7; ++$i)
		{
			$this->configurator->Keywords->add(str_repeat($i, 9000));
		}

		$config = $this->configurator->Keywords->asConfig();

		$this->assertArrayHasKey('regexps', $config);
		$this->assertEquals(
			[
				'/\b(?>' . str_repeat('0', 9000) . '|' . str_repeat('1', 9000) . '|' . str_repeat('2', 9000) . ')\b/S',
				'/\b(?>' . str_repeat('3', 9000) . '|' . str_repeat('4', 9000) . '|' . str_repeat('5', 9000) . ')\b/S',
				'/\b' . str_repeat('6', 9000) . '\b/S'
			],
			$config['regexps']
		);
	}
}