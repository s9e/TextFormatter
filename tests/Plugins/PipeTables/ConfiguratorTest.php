<?php

namespace s9e\TextFormatter\Tests\Plugins\PipeTables;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\PipeTables\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Creates TABLE, TBODY, TD, TH, THEAD and TR tags
	*/
	public function testCreatesTags()
	{
		$this->configurator->PipeTables;
		$this->assertTrue(isset($this->configurator->tags['TABLE']));
		$this->assertTrue(isset($this->configurator->tags['TBODY']));
		$this->assertTrue(isset($this->configurator->tags['TD']));
		$this->assertTrue(isset($this->configurator->tags['TH']));
		$this->assertTrue(isset($this->configurator->tags['THEAD']));
		$this->assertTrue(isset($this->configurator->tags['TR']));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tags     = [];
		$tagNames = ['TABLE', 'THEAD', 'TBODY', 'TR', 'TD', 'TH'];
		foreach ($tagNames as $tagName)
		{
			$tags[$tagName] = $this->configurator->tags->add($tagName);
		}
		$this->configurator->PipeTables;
		foreach ($tags as $tagName => $tag)
		{
			$this->assertSame($tag, $this->configurator->tags[$tagName]);
		}
	}

	/**
	* @testdox asConfig() sets overwriteEscapes to false by default
	*/
	public function testAsConfigOverwriteEscapesFalse()
	{
		$config = $this->configurator->PipeTables->asConfig();
		$this->assertFalse($config['overwriteEscapes']);
	}

	/**
	* @testdox asConfig() sets overwriteEscapes to true if the Escaper plugin is loaded
	*/
	public function testAsConfigOverwriteEscapesTrue()
	{
		$this->configurator->PipeTables;
		$this->configurator->Escaper;

		$config = $this->configurator->PipeTables->asConfig();
		$this->assertTrue($config['overwriteEscapes']);
	}

	/**
	* @testdox asConfig() sets overwriteMarkdown to false by default
	*/
	public function testAsConfigOverwriteMarkdownFalse()
	{
		$config = $this->configurator->PipeTables->asConfig();
		$this->assertFalse($config['overwriteMarkdown']);
	}

	/**
	* @testdox asConfig() sets overwriteMarkdown to true if the Litedown plugin is loaded
	*/
	public function testAsConfigOverwriteMarkdownTrue()
	{
		$this->configurator->PipeTables;
		$this->configurator->Litedown;

		$config = $this->configurator->PipeTables->asConfig();
		$this->assertTrue($config['overwriteMarkdown']);
	}
}