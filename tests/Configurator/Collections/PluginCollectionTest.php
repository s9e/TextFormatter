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
		$this->configurator->plugins->load('HTMLElements');
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\HTMLElements\\Configurator',
			$this->configurator->plugins['HTMLElements']
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

	/**
	* @testdox asConfig() does not return an entry for plugins whose asConfig() returns NULL
	*/
	public function testAsConfigRemoveNull()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig(null);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->plugins->asConfig();

		$this->assertSame(
			[],
			$config
		);
	}

	/**
	* @testdox asConfig() returns an entry for plugins whose asConfig() returns an empty array
	*/
	public function testAsConfigKeepEmpty()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig([]);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->plugins->asConfig();

		$this->assertArrayHasKey('Dummy', $config);
	}

	/**
	* @testdox asConfig() adds regexpLimit to the plugin's configuration if it's not specified and the plugin has a regexp
	*/
	public function testAsConfigAddRegexpLimit()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig(['regexp' => '//']);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->plugins->asConfig();

		$this->assertArrayMatches(
			[
				'Dummy' => [
					'regexpLimit' => 50000
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() removes regexpLimit from the plugin's configuration if it does not have a regexp
	*/
	public function testAsConfigRemoveRegexpLimit()
	{
		$plugin = new DummyPluginConfigurator($this->configurator);
		$plugin->setConfig(['regexpLimit' => 1000]);

		$this->configurator->plugins->add('Dummy',$plugin);
		$config = $this->configurator->plugins->asConfig();

		$this->assertArrayMatches(
			[
				'Dummy' => [
					'regexpLimit' => null
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() adds quickMatch to the plugin's configuration if available
	*/
	public function testAsConfigAddQuickMatch()
	{
		$this->configurator->plugins->add(
			'Dummy',
			new DummyPluginConfigurator($this->configurator)
		)->setQuickMatch('foo');
		$config = $this->configurator->plugins->asConfig();

		$this->assertArrayMatches(
			[
				'Dummy' => [
					'quickMatch' => 'foo'
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() omits a plugin's quickMatch if it's false
	*/
	public function testAsConfigOmitsQuickMatch()
	{
		$this->configurator->plugins->add(
			'Dummy',
			new DummyPluginConfigurator($this->configurator)
		);
		$config = $this->configurator->plugins->asConfig();

		$this->assertArrayMatches(
			[
				'Dummy' => [
					'quickMatch' => null
				]
			],
			$config
		);
	}

	/**
	* @testdox asConfig() removes className from the plugin's configuration if it's using its default value
	*/
	public function testAsConfigRemoveClassName()
	{
		$this->configurator->plugins->load('Autolink');

		$this->assertArrayMatches(
			[
				'Autolink' => [
					'className' => null
				]
			],
			$this->configurator->plugins->asConfig()
		);
	}

	/**
	* @testdox asConfig() preserves className from the plugin's configuration if it's using a custom value
	*/
	public function testAsConfigPreservesClassName()
	{
		$plugin = new \s9e\TextFormatter\Plugins\Autolink\Configurator($this->configurator);
		$this->configurator->plugins->add('Foo', $plugin);

		$this->assertArrayMatches(
			[
				'Foo' => [
					'className' => 's9e\\TextFormatter\\Plugins\\Autolink\\Parser'
				]
			],
			$this->configurator->plugins->asConfig()
		);
	}

	/**
	* @testdox finalize() calls every plugin's finalize() method
	*/
	public function testFinalize()
	{
		$plugin1 = new DummyPluginConfigurator($this->configurator);
		$plugin2 = new DummyPluginConfigurator($this->configurator);
		$this->configurator->plugins->add('Foo1', $plugin1);
		$this->configurator->plugins->add('Foo2', $plugin2);

		$this->configurator->plugins->finalize();

		$this->assertSame(1, $plugin1->finalized);
		$this->assertSame(1, $plugin2->finalized);
	}
}

class DummyPluginConfigurator extends ConfiguratorBase
{
	protected $config = ['foo' => 1];
	public $finalized = 0;

	public function asConfig()
	{
		return $this->config;
	}

	public function finalize()
	{
		++$this->finalized;
	}

	public function setConfig($config)
	{
		$this->config = $config;
	}
}