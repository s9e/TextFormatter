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

	//==========================================================================
	// Namespaces
	//==========================================================================

	public function testCanRegisterNamespaceWithValidPrefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertSame(
			array('foo' => 'urn:foo'),
			$this->cb->getNamespaces()
		);
	}

	/**
	* @testdox Throws an exception if an attempt is made to register a namespace with the prefix 'xsl'
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage reserved for internal use
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToRegisterXSLPrefix()
	{
		$this->cb->registerNamespace('xsl', 'urn:foo');
	}

	/**
	* @testdox Throws an exception if an attempt is made to register a namespace with the URI 'http://www.w3.org/1999/XSL/Transform'
	* @depends testCanRegisterNamespaceWithValidPrefix
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage reserved
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToRegisterXSLURI()
	{
		$this->cb->registerNamespace('foo', 'http://www.w3.org/1999/XSL/Transform');
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid prefix name 'foo>'
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToRegisterANamespaceWithAnInvalidPrefix()
	{
		$this->cb->registerNamespace('foo>', 'urn:foo');
	}

	/**
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Prefix 'foo' is already registered to namespace 'urn:foo'
	*/
	public function testThrowsAnExceptionIfAnAttemptIsMadeToOverwriteARegisteredNamespace()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('foo', 'urn:bar');
	}

	public function testDoesNotThrowAnExceptionIfANamespaceIsRegisteredIdenticallyMultipleTimes()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('foo', 'urn:foo');
	}

	public function testCanTellWhetherANamespaceHasBeenRegisteredToGivenPrefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertTrue($this->cb->namespaceExists('foo'));
		$this->assertFalse($this->cb->namespaceExists('bar'));
	}

	public function testCanUnregisterANamespaceByItsPrefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->unregisterNamespace('foo');

		$this->assertFalse($this->cb->namespaceExists('foo'));
	}

	public function testCanReturnAListOfAllRegisteredNamespaces()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('bar', 'urn:bar');

		$this->assertEquals(
			array('foo' => 'urn:foo', 'bar' => 'urn:bar'),
			$this->cb->getNamespaces()
		);
	}

	/**
	* @test
	*/
	public function Can_return_the_URI_associated_with_a_namespace_prefix()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertSame('urn:foo', $this->cb->getNamespaceURI('foo'));
	}

	/**
	* @test
	*/
	public function Can_return_the_first_prefix_associated_with_a_namespace_URI()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');

		$this->assertSame('foo', $this->cb->getNamespacePrefix('urn:foo'));
	}

	/**
	* @test
	*/
	public function Can_return_all_the_prefixes_associated_with_a_namespace_URI()
	{
		$this->cb->registerNamespace('foo', 'urn:foo');
		$this->cb->registerNamespace('foo2', 'urn:foo');

		$this->assertEquals(
			array('foo', 'foo2'),
			$this->cb->getNamespacePrefixes('urn:foo')
		);
	}
}