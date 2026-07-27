<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoaudio;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\AbstractStaticUrlReplacer\AbstractConfigurator
* @covers s9e\TextFormatter\Plugins\Autoaudio\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "AUDIO" tag with a "src" attribute with a "#url" filter
	*/
	public function testCreatesTag()
	{
		$this->configurator->Autoaudio;
		$this->assertTrue($this->configurator->tags->exists('AUDIO'));

		$tag = $this->configurator->tags->get('AUDIO');
		$this->assertTrue($tag->attributes->exists('src'));

		$attribute = $tag->attributes->get('src');
		$this->assertTrue($attribute->filterChain->contains(new UrlFilter));
	}

	/**
	* @testdox Automatically creates a "filename" attribute
	*/
	public function testCreatesFilenameAttribute()
	{
		$this->configurator->Autoaudio;
		$tag = $this->configurator->tags->get('AUDIO');
		$this->assertTrue($tag->attributes->exists('filename'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('AUDIO');
		$this->configurator->plugins->load('Autoaudio');

		$this->assertSame($tag, $this->configurator->tags->get('AUDIO'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Autoaudio', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Autoaudio', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['AUDIO']->attributes->exists('bar'));
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('Autoaudio')->asConfig()
		);
	}

	/**
	* @testdox The config array contains a regexp
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('Autoaudio')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$config = $this->configurator->plugins->load('Autoaudio')->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('AUDIO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttributeName()
	{
		$config = $this->configurator->plugins->load('Autoaudio')->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('src', $config['attrName']);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Autoaudio');

		$this->assertSame(
			$this->configurator->tags['AUDIO'],
			$plugin->getTag()
		);
	}

	/**
	* @testdox The JS parser contains both parser files
	*/
	public function testJSParser()
	{
		$js = $this->configurator->Autoaudio->getJSParser();

		foreach (['AbstractStaticUrlReplacer', 'Autoaudio'] as $pluginName)
		{
			$filepath = __DIR__ . '/../../../src/Plugins/' . $pluginName . '/Parser.js';
			$this->assertStringContainsString(file_get_contents($filepath), $js);
		}
	}

	/**
	* @testdox File extensions are configurable
	*/
	public function testFileExtensions()
	{
		$this->configurator->Autoaudio->fileExtensions = ['flac', 'wma'];
		$this->configurator->Autoaudio->finalize();

		$config = $this->configurator->Autoaudio->asConfig();

		$this->assertMatchesRegularExpression(
			$config['regexp'],
			'https://example.org/audio.flac'
		);
		$this->assertDoesNotMatchRegularExpression(
			$config['regexp'],
			'https://example.org/audio.mp4'
		);
	}

	/**
	* @testdox The default template contains CSS classes for styling
	*/
	public function testTemplateCSSClasses()
	{
		$this->configurator->Autoaudio;
		$template = (string) $this->configurator->tags['AUDIO']->template;

		$this->assertStringContainsString('class="autoaudio"', $template);
		$this->assertStringContainsString('class="autoaudio-title"', $template);
		$this->assertStringContainsString('class="autoaudio-player"', $template);
	}

	/**
	* @testdox The template can be overridden after configuration
	*/
	public function testTemplateOverride()
	{
		$this->configurator->Autoaudio;
		$tag = $this->configurator->tags['AUDIO'];
		$tag->template = '<audio controls="" class="custom" src="{@src}"/>';

		$this->assertStringContainsString('class="custom"', (string) $tag->template);
		$this->assertStringNotContainsString('autoaudio', (string) $tag->template);
	}
}
