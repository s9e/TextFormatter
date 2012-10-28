<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use Exception;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Collections\PluginCollection
*/
class PluginCollectionTest extends Test
{
	/**
	* @testdox load() can load a stock plugin
	*/
	public function testLoad()
	{
		$this->configurator->plugins->load('RawHTML');
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\RawHTML\\Configurator',
			$this->configurator->plugins['RawHTML']
		);
	}

	/**
	* @testdox load() throws an exception if the stock plugin does not exist
	* @expectedException RuntimeException
	* @expectedExceptionMessage Class 's9e\TextFormatter\Plugins\Unknown\Configurator
	*/
	public function testLoadUnknown()
	{
		$this->configurator->plugins->load('Unknown');
	}

	/**
	* @testdox Accepts an object that implements ConfiguratorBase
	*/
	public function testAddInstance()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);

		$this->assertSame(
			$plugin,
			$this->configurator->plugins->add('Dummy', $plugin)
		);
	}

	/**
	* @testdox Accepts a string that is the name of a class that implements ConfiguratorBase
	*/
	public function testAddClassName()
	{
		$className = __NAMESPACE__ . '\\DummyPluginConfigurator';

		$this->assertInstanceOf(
			$className,
			$this->configurator->plugins->add('Dummy', $className)
		);
	}

	/**
	* @testdox Throws an exception if the value is neither an instance of or the name of a class that implements ConfiguratorBase
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage PluginCollection::normalizeValue() expects a class name or an object that implements s9e\TextFormatter\Plugins\ConfiguratorBase
	*/
	public function testInvalidValue()
	{
		$this->configurator->plugins->add('Dummy', new \stdClass);
	}

	/**
	* @testdox Throws an exception if the plugin name is not entirely composed of letters, numbers and underscores
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid plugin name 'Dum-my'
	*/
	public function testInvalidNameChars()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$this->configurator->plugins->add('Dum-my', $plugin);
	}

	/**
	* @testdox Throws an exception if the plugin name does not start with an uppercase letter
	* @expectedException InvalidArgumentException
	* @expectedExceptionMessage Invalid plugin name 'dummy'
	*/
	public function testInvalidInitial()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$this->configurator->plugins->add('dummy', $plugin);
	}
}

class DummyPluginConfigurator extends ConfiguratorBase
{
}