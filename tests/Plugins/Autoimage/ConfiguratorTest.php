<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoimage;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autoimage\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "IMG" tag with a "src" attribute with a "#url" filter
	*/
	public function testCreatesTag()
	{
		$this->configurator->Autoimage;
		$this->assertTrue($this->configurator->tags->exists('IMG'));

		$tag = $this->configurator->tags->get('IMG');
		$this->assertTrue($tag->attributes->exists('src'));

		$attribute = $tag->attributes->get('src');
		$this->assertTrue($attribute->filterChain->contains(new UrlFilter));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('IMG');
		$this->configurator->plugins->load('Autoimage');

		$this->assertSame($tag, $this->configurator->tags->get('IMG'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Autoimage', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Autoimage', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['IMG']->attributes->exists('bar'));
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('Autoimage')->asConfig()
		);
	}

	/**
	* @testdox The config array contains a regexp
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('Autoimage')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$config = $this->configurator->plugins->load('Autoimage')->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('IMG', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttributeName()
	{
		$config = $this->configurator->plugins->load('Autoimage')->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('src', $config['attrName']);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Autoimage');

		$this->assertSame(
			$this->configurator->tags['IMG'],
			$plugin->getTag()
		);
	}
}