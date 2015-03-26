<?php

namespace s9e\TextFormatter\Tests\Plugins\Autoemail;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\EmailFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\Autoemail\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an "EMAIL" tag with an "email" attribute with a "#email" filter
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Autoemail');
		$this->assertTrue($this->configurator->tags->exists('EMAIL'));

		$tag = $this->configurator->tags->get('EMAIL');
		$this->assertTrue($tag->attributes->exists('email'));

		$attribute = $tag->attributes->get('email');
		$this->assertTrue($attribute->filterChain->contains(new EmailFilter));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('EMAIL');
		$this->configurator->plugins->load('Autoemail');

		$this->assertSame($tag, $this->configurator->tags->get('EMAIL'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Autoemail', ['tagName' => 'FOO']);
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Autoemail', ['attrName' => 'bar']);
		$this->assertTrue($this->configurator->tags['EMAIL']->attributes->exists('bar'));
	}

	/**
	* @testdox Has a quickMatch
	*/
	public function testConfigQuickMatch()
	{
		$this->assertArrayHasKey(
			'quickMatch',
			$this->configurator->plugins->load('Autoemail')->asConfig()
		);
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testConfigRegexp()
	{
		$this->assertArrayHasKey(
			'regexp',
			$this->configurator->plugins->load('Autoemail')->asConfig()
		);
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$config = $this->configurator->plugins->load('Autoemail')->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('EMAIL', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttributeName()
	{
		$config = $this->configurator->plugins->load('Autoemail')->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('email', $config['attrName']);
	}

	/**
	* @testdox getTag() returns the tag that is associated with this plugin
	*/
	public function testGetTag()
	{
		$plugin = $this->configurator->plugins->load('Autoemail');

		$this->assertSame(
			$this->configurator->tags['EMAIL'],
			$plugin->getTag()
		);
	}
}