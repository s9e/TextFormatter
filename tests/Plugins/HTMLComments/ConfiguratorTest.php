<?php

namespace s9e\TextFormatter\Tests\Plugins\HTMLComments;

use s9e\TextFormatter\Plugins\HTMLComments\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\HTMLComments\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "HC" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->HTMLComments;
		$this->assertTrue($this->configurator->tags->exists('HC'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('HTMLComments', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('HTMLComments', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['HC']->attributes->exists('bar'));
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('HTMLComments')->asConfig()
		);
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('HTMLComments')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$config = $this->configurator->plugins->load('HTMLComments')->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('HC', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('HTMLComments', ['attrName' => 'bar']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('HTMLComments');

		$this->assertSame(
			$this->configurator->tags['HC'],
			$plugin->getTag()
		);
	}
}