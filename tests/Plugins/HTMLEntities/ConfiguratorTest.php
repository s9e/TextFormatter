<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLEntities;

use s9e\TextFormatter\Plugins\HTMLEntities\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLEntities\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "HE" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('HTMLEntities');
		$this->assertTrue($this->configurator->tags->exists('HE'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('HTMLEntities', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('HTMLEntities', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['HE']->attributes->exists('bar'));
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('HTMLEntities')->asConfig()
		);
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('HTMLEntities')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$config = $this->configurator->plugins->load('HTMLEntities')->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('HE', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('HTMLEntities', ['attrName' => 'bar']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('HTMLEntities');

		$this->assertSame(
			$this->configurator->tags['HE'],
			$plugin->getTag()
		);
	}
}