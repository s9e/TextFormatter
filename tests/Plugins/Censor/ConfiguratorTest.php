<?php

namespace s9e\TextFormatter\Tests\Plugins\Censor;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\Censor\Configurator;

/**
* @covers s9e\TextFormatter\Plugins\Censor\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates a "CENSOR" tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Censor');
		$this->assertTrue($this->configurator->tags->exists('CENSOR'));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
		$tag = $this->configurator->tags->add('CENSOR');
		$this->configurator->plugins->load('Censor');

		$this->assertSame($tag, $this->configurator->tags->get('CENSOR'));
	}

	/**
	* @testdox The name of the tag used can be changed through the "tagName" constructor option
	*/
	public function testCustomTagName()
	{
		$this->configurator->plugins->load('Censor', array('tagName' => 'FOO'));
		$this->assertTrue($this->configurator->tags->exists('FOO'));
	}

	/**
	* @testdox The name of the attribute used can be changed through the "attrName" constructor option
	*/
	public function testCustomAttrName()
	{
		$this->configurator->plugins->load('Censor', array('attrName' => 'bar'));
		$this->assertTrue($this->configurator->tags['CENSOR']->attributes->exists('bar'));
	}

	/**
	* @testdox asConfig() returns FALSE if no words were added
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$this->assertFalse($plugin->asConfig());
	}

	/**
	* @testdox Generates a regexp for its config array
	*/
	public function testAsConfig()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple');

		$this->assertArrayHasKey('regexp', $plugin->asConfig());
	}

	/**
	* @testdox The config array contains the name of the tag
	*/
	public function testConfigTagName()
	{
		$plugin = $this->configurator->plugins->load('Censor', array('tagName' => 'FOO'));
		$plugin->add('apple');

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('tagName', $config);
		$this->assertSame('FOO', $config['tagName']);
	}

	/**
	* @testdox The config array contains the name of the attribute
	*/
	public function testConfigAttrName()
	{
		$plugin = $this->configurator->plugins->load('Censor', array('attrName' => 'bar'));
		$plugin->add('apple');

		$config = $plugin->asConfig();

		$this->assertArrayHasKey('attrName', $config);
		$this->assertSame('bar', $config['attrName']);
	}

	/**
	* @testdox Returns the replacements in its config in the form [regexp => replacement]
	*/
	public function testAsConfigReplacements()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple', 'banana');

		$config = $plugin->asConfig();

		$this->assertSame(
			array('/^apple$/Diu' => 'banana'),
			$config['replacements']
		);
	}

	/**
	* @testdox Words sharing the same replacement share a common regexp
	*/
	public function testAsConfigReplacementsMerge()
	{
		$plugin = $this->configurator->plugins->load('Censor');
		$plugin->add('apple', 'banana');
		$plugin->add('cherry', 'banana');

		$config = $plugin->asConfig();

		$this->assertSame(
			array('/^(?:apple|cherry)$/Diu' => 'banana'),
			$config['replacements']
		);
	}

	/**
	* @testdox Words using the default replacement do not appear in the replacements
	*/
	public function testAsConfigDefaultReplacement()
	{
		$plugin = $this->configurator->plugins->load('Censor', array('defaultReplacement' => '**'));
		$plugin->add('apple', '**');
		$plugin->add('cherry', 'banana');

		$config = $plugin->asConfig();

		$this->assertSame(
			array('/^cherry$/Diu' => 'banana'),
			$config['replacements']
		);
	}
}