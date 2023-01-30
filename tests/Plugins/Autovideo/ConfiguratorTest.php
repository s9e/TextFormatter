<?php

namespace s9e\TextFormatter\Tests\Plugins\Autovideo;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer\AbstractConfigurator
* @covers s9e\TextFormatter\Plugins\Autovideo\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates a "VIDEO" tag with a "src" attribute with a "#url" filter
	*/
	public function testCreatesTag()
	{
		$this->configurator->Autovideo;
		$this->assertTrue($this->configurator->tags->exists('VIDEO'));

		$tag = $this->configurator->tags->get('VIDEO');
		$this->assertTrue($tag->attributes->exists('src'));

		$attribute = $tag->attributes->get('src');
		$this->assertTrue($attribute->filterChain->contains(new UrlFilter));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('VIDEO');
		$this->configurator->plugins->load('Autovideo');

		$this->assertSame($tag, $this->configurator->tags->get('VIDEO'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Autovideo', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Autovideo', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['VIDEO']->attributes->exists('bar'));
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('Autovideo')->asConfig()
		);
	}

	/**
	* @testdox The config array contains a regexp
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('Autovideo')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$config = $this->configurator->plugins->load('Autovideo')->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('VIDEO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttributeName()
	{
		$config = $this->configurator->plugins->load('Autovideo')->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('src', $config['attrName']);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Autovideo');

		$this->assertSame(
			$this->configurator->tags['VIDEO'],
			$plugin->getTag()
		);
	}

	/**
	* @testdox File extensions are configurable
	*/
	public function testFileExtensions()
	{
		$this->configurator->Autovideo->fileExtensions = ['blorpv', 'navi'];
		$this->configurator->Autovideo->finalize();

		$config = $this->configurator->Autovideo->asConfig();

		$this->assertMatchesRegularExpression(
			$config['regexp'],
			'https://example.org/video.blorpv'
		);
		$this->assertDoesNotMatchRegularExpression(
			$config['regexp'],
			'https://example.org/video.mp5'
		);
	}
}