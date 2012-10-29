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

	/**
	* @testdox setRegexpLimit() sets the regexpLimit property
	*/
	public function testSetRegexpLimit()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setRegexpLimit(1234);
		$this->assertAttributeEquals(1234, 'regexpLimit', $dummy);
	}

	/**
	* @testdox setRegexpLimit() throws an exception on invalid values
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage regexpLimit must be a number greater than 0
	*/
	public function testSetRegexpLimitInvalid()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setRegexpLimit(null);
	}

	/**
	* @testdox setRegexpLimitAction() sets the regexpLimitAction property
	*/
	public function testSetRegexpLimitAction()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setRegexpLimitAction('abort');
		$this->assertAttributeEquals('abort', 'regexpLimitAction', $dummy);
	}

	/**
	* @testdox setRegexpLimitAction() throws an exception on invalid values
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage regexpLimitAction must be any of: 'ignore', 'warn' or 'abort'
	*/
	public function testSetRegexpLimitActionInvalid()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setRegexpLimitAction('chill out');
	}

	/**
	* @testdox Offers a default asConfig() implementation that leaves out the configurator instance
	*/
	public function testToConfig()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);

		$this->assertEquals(
			array(
				'foo' => 'foo',
				'bar' => 'foo',
				'regexpLimit' => 1000,
				'regexpLimitAction' => 'ignore'
			),
			$dummy->asConfig()
		);
	}

	/**
	* @testdox Offers a default getXSL() implementation that returns an empty string
	*/
	public function testGetXSL()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);

		$this->assertSame('', $dummy->getXSL());
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