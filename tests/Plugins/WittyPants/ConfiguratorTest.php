<?php

namespace s9e\TextFormatter\Tests\Plugins\WittyPants;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\WittyPants\Configurator;

/**
* @covers s9e\TextFormatter\Plugins\WittyPants\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates a "WP" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('WittyPants');
		$this->assertTrue($this->configurator->tags->exists('WP'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('WP');
		$this->configurator->plugins->load('WittyPants');

		$this->assertSame($tag, $this->configurator->tags->get('WP'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('WittyPants', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('WittyPants', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['WP']->attributes->exists('bar'));
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('WittyPants', ['tagName' => 'FOO']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('WittyPants', ['attrName' => 'bar']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}
}