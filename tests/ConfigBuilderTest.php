<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Tests\Test,
    s9e\TextFormatter\ConfigBuilder;

include_once __DIR__ . '/../src/autoloader.php';

/**
* @covers s9e\TextFormatter\ConfigBuilder
*/
class ConfigBuilderTest extends Test
{
	//==========================================================================
	// Plugins
	//==========================================================================

	public function testCanLoadPlugins()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodesConfig',
			$this->cb->loadPlugin('BBCodes')
		);
	}

	public function testLoadedPluginsAreAvailableAsAPublicProperty()
	{
		$this->cb->loadPlugin('BBCodes');

		$this->assertObjectHasAttribute('BBCodes', $this->cb);
		$this->assertTrue(isset($this->cb->BBCodes), 'Could not assert that $this->cb->BBCodes is set');
	}

	public function testCanUnloadPluginsByUnsettingThem()
	{
		$this->cb->loadPlugin('BBCodes');
		unset($this->cb->BBCodes);

		$this->assertObjectNotHasAttribute('BBCodes', $this->cb);
	}

	/**
	* @testdox Loads plugins on magic __get()
	*/
	public function testLoadsPluginOnMagicGet()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\BBCodesConfig',
			$this->cb->BBCodes
		);
	}

	/**
	* @testdox Has a magic property $predefinedTags that creates an instance of PredefinedTags on access
	*/
	public function Has_a_magic_property_predefinedTags_that_creates_an_instance_of_PredefinedTags()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\ConfigBuilder\\PredefinedTags',
			$this->cb->predefinedTags
		);
	}

	/**
	* @testdox Throws a RuntimeException on unsupported magic __get()
	* @expectedException RuntimeException
	* @expectedExceptionMessage Undefined property 's9e\TextFormatter\ConfigBuilder::$foo'
	*/
	public function testThrowsAnExceptionOnUnsupportedMagicGet()
	{
		$this->cb->foo;
	}

	/**
	* @testdox loadPlugin() throws an exception on invalid plugin name
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid plugin name '../foo'
	*/
	public function loadPlugin_throws_an_exception_on_invalid_plugin_name()
	{
		$this->cb->loadPlugin('../foo');
	}

	/**
	* @testdox loadPlugin() throws an exception on unknown plugin
	* @expectedException RuntimeException
	* @expectedExceptionMessage Class 's9e\TextFormatter\Plugins\FoobarConfig' does not exist
	*/
	public function loadPlugin_throws_an_exception_on_unknown_plugin()
	{
		$this->cb->loadPlugin('Foobar');
	}
}