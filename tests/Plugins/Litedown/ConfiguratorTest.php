<?php

namespace s9e\TextFormatter\Tests\Plugins\Litedown;

use s9e\TextFormatter\Plugins\Litedown\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Litedown\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Turns on automatic paragraph management
	*/
	public function testManageParagraphs()
	{
		$this->configurator->plugins->load('Litedown');
		$this->assertTrue($this->configurator->rulesGenerator->contains('ManageParagraphs'));
	}

	/**
	* @testdox Automatically creates a "URL" tag
	*/
	public function testCreatesURL()
	{
		$this->configurator->plugins->load('Litedown');
		$this->assertTrue($this->configurator->tags->exists('URL'));
	}

	/**
	* @testdox Does not overwrite the "URL" tag if it already exists
	*/
	public function testPreservesURL()
	{
		$tag       = $this->configurator->tags->add('URL');
		$tagConfig = $tag->asConfig();

		$this->configurator->plugins->load('Litedown');

		$this->assertSame($tag, $this->configurator->tags->get('URL'));
		$this->assertEquals($tagConfig, $tag->asConfig());
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$this->assertInternalType('array', $this->configurator->Litedown->asConfig());
	}
}