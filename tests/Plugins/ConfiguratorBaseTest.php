<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\ConfiguratorBase
*/
class ConfiguratorBaseTest extends Test
{
	/**
	* @testdox Constructor overwrites properties with values passed as second argument
	*/
	public function testOverwrite()
	{
		$dummy = new DummyPluginConfigurator($this->configurator, array('bar' => 'bar'));
		$this->assertSame('bar', $dummy->bar);
	}

	/**
	* @testdox Constructor calls setFoo() if 'foo' is being set
	*/
	public function testOverwriteMethod()
	{
		$dummy = new DummyPluginConfigurator($this->configurator, array('foo' => 'bar'));
		$this->assertSame('baz', $dummy->foo);
	}

	/**
	* @testdox An exception is thrown if an unknown property is being set by the constructor
	* @expectedException RuntimeException
	* @expectedExceptionMessage Unknown property 'baz'
	*/
	public function testUnknownProperty()
	{
		new DummyPluginConfigurator($this->configurator, array('baz' => 'baz'));
	}
}

class DummyPluginConfigurator extends ConfiguratorBase
{
	public $foo = 'foo';
	public $bar = 'foo';

	public function setFoo()
	{
		$this->foo = 'baz';
	}
}