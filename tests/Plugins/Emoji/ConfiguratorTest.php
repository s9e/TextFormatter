<?php

namespace s9e\TextFormatter\Tests\Plugins\Emoji;

use s9e\TextFormatter\Plugins\Emoji\Configurator;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Emoji\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "EMOJI" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Emoji');
		$this->assertTrue($this->configurator->tags->exists('EMOJI'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('EMOJI');
		$this->configurator->plugins->load('Emoji');

		$this->assertSame($tag, $this->configurator->tags->get('EMOJI'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Emoji', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['EMOJI']->attributes->exists('bar'));
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('Emoji', ['tagName' => 'FOO']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('Emoji', ['attrName' => 'bar']);

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox Can use the EmojiOne set
	*/
	public function testTemplateEmojiOne()
	{
		$this->configurator->Emoji->useEmojiOne();
		$this->assertContains('emojione', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Can use the Twemoji set
	*/
	public function testTemplateTwemoji()
	{
		$this->configurator->Emoji->useTwemoji();
		$this->assertContains('twemoji', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Uses Twemoji by default
	*/
	public function testDefaultTemplateTwemoji()
	{
		$this->configurator->Emoji;
		$this->assertContains('twemoji', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji set can use PNG images
	*/
	public function testTwemojiPNG()
	{
		$this->configurator->Emoji->usePNG();
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji set can use SVG images
	*/
	public function testTwemojiSVG()
	{
		$this->configurator->Emoji->useSVG();
		$this->assertContains('svg', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox Twemoji set uses PNG images by default
	*/
	public function testTwemojiDefaultPNG()
	{
		$this->configurator->Emoji;
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox EmojiOne set can use PNG images
	*/
	public function testEmojiOnePNG()
	{
		$this->configurator->Emoji->usePNG();
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox EmojiOne set can use SVG images
	*/
	public function testEmojiOneSVG()
	{
		$this->configurator->Emoji->useSVG();
		$this->assertContains('svg', (string) $this->configurator->tags['EMOJI']->template);
	}

	/**
	* @testdox EmojiOne set uses PNG by Default
	*/
	public function testEmojiOneDefaultPNG()
	{
		$this->configurator->Emoji;
		$this->assertContains('png', (string) $this->configurator->tags['EMOJI']->template);
	}
}