<?php

namespace s9e\TextFormatter\Tests\Plugins\FancyPants;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\FancyPants\Configurator;

/**
* @covers s9e\TextFormatter\Plugins\FancyPants\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates a "FP" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('FancyPants');
		$this->assertTrue($this->configurator->tags->exists('FP'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('FP');
		$this->configurator->plugins->load('FancyPants');

		$this->assertSame($tag, $this->configurator->tags->get('FP'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('FancyPants', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('FancyPants', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['FP']->attributes->exists('bar'));
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('FancyPants', ['tagName' => 'FOO']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('FancyPants', ['attrName' => 'bar']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('FancyPants');

		$this->assertSame(
			$this->configurator->tags['FP'],
			$plugin->getTag()
		);
	}

	/**
	* @testdox The config array does not contain a disableQuotes value by default
	*/
	public function testDisableQuotesDefault()
	{
		$config = $this->configurator->FancyPants->asConfig();

		$this->assertArrayNotHasKey('disableQuotes', $config);
	}

	/**
	* @testdox The config array contains a disableQuotes value if the quote pass is disabled
	*/
	public function testDisableQuotesDisabled()
	{
		$this->configurator->FancyPants->disablePass('Quotes');

		$config = $this->configurator->FancyPants->asConfig();

		$this->assertArrayHasKey('disableQuotes', $config);
		$this->assertTrue($config['disableQuotes']);
	}

	/**
	* @testdox The config array does not contain a disableQuotes value if the quote pass is re-enabled
	*/
	public function testDisableQuotesEnabled()
	{
		$this->configurator->FancyPants->disablePass('Quotes');
		$this->configurator->FancyPants->enablePass('Quotes');

		$config = $this->configurator->FancyPants->asConfig();

		$this->assertArrayNotHasKey('disableQuotes', $config);
	}
}