<?php

namespace s9e\TextFormatter\Tests\Plugins;

use s9e\TextFormatter\Configurator;
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
	* @testdox getBaseProperties() return the values of quickMatch, regexpLimit and regexpLimitAction
	*/
	public function testGetBaseProperties()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$config = $dummy->getBaseProperties();

		$this->assertArrayHasKey('quickMatch', $config);
		$this->assertArrayHasKey('regexpLimit', $config);
		$this->assertArrayHasKey('regexpLimitAction', $config);
	}

	/**
	* @testdox setQuickMatch() sets the quickMatch property
	*/
	public function testSetQuickMatch()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setQuickMatch('@');
		$this->assertAttributeEquals('@', 'quickMatch', $dummy);
	}

	/**
	* @testdox setQuickMatch() throws an exception on non-strings
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage quickMatch must be a string
	*/
	public function testSetQuickMatchInvalid()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setQuickMatch(null);
	}

	/**
	* @testdox disableQuickMatch() sets the quickMatch property to false
	*/
	public function testDisableQuickMatch()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);
		$dummy->setQuickMatch('@');
		$dummy->disableQuickMatch();
		$this->assertAttributeEquals(false, 'quickMatch', $dummy);
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
	public function testAsConfig()
	{
		$dummy = new DummyPluginConfigurator($this->configurator);

		$this->assertEquals(
			array(
				'foo' => 'foo',
				'bar' => 'foo',
				'quickMatch'  => false,
				'regexpLimit' => 1000,
				'regexpLimitAction' => 'warn'
			),
			$dummy->asConfig()
		);
	}

	/**
	* @testdox getJSParser() returns NULL for custom plugins
	*/
	public function testGetJSParserFalse()
	{
		$dummy = new DummyPluginConfigurator(new Configurator);
		$this->assertNull($dummy->getJSParser());
	}

	/**
	* @testdox getJSParser() returns the content of Parser.js for stock plugins
	*/
	public function testGetJSParserStock()
	{
		$configurator = new Configurator;

		$this->assertStringEqualsFile(
			__DIR__ . '/../../src/s9e/TextFormatter/Plugins/Autolink/Parser.js',
			$configurator->Autolink->getJSParser()
		);
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