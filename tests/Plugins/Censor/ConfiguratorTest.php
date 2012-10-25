<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\Censor\Configurator;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates a "CENSOR" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Censor');
		$this->assertTrue($this->configurator->tags->exists('CENSOR'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('CENSOR');
		$this->configurator->plugins->load('Censor');

		$this->assertSame($tag, $this->configurator->tags->get('CENSOR'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Censor', array('tagName' => 'FOO'));
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Censor', array('attrName' => 'bar'));
		$this->assertTrue($this->configurator->tags['CENSOR']->attributes->exists('bar'));
	}

	/**
	* @testdox toConfig() returns FALSE if no words were added
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$this->assertFalse($plugin->toConfig());
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testToConfig()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple');

		$this->assertArrayHasKey('regexp', $plugin->toConfig());
	}
}