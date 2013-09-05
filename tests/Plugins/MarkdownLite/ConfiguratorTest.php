<?php

namespace s9e\TextFormatter\Tests\Plugins\MarkdownLite;

use s9e\TextFormatter\Plugins\MarkdownLite\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\MarkdownLite\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Turns on automatic paragraph management
	*/
	public function testManageParagraphs()
	{
		$this->configurator->plugins->load('MarkdownLite');
		$this->assertTrue($this->configurator->rulesGenerator->contains('ManageParagraphs'));
	}

	/**
	* @testdox Automatically creates a "URL" tag
	*/
	public function testCreatesURL()
	{
		$this->configurator->plugins->load('MarkdownLite');
		$this->assertTrue($this->configurator->tags->exists('URL'));
	}

	/**
	* @testdox Does not overwrite the "URL" tag if it already exists
	*/
	public function testPreservesURL()
	{
		$tag       = $this->configurator->tags->add('URL');
		$tagConfig = $tag->asConfig();

		$this->configurator->plugins->load('MarkdownLite');

		$this->assertSame($tag,       $this->configurator->tags->get('URL'));
		$this->assertSame($tagConfig, $tag->asConfig());
	}

	/**
	* @testdox asConfig() returns an array
	*/
	public function testAsConfig()
	{
		$this->assertInternalType('array', $this->configurator->MarkdownLite->asConfig());
	}
}