<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Plugins\ConfiguratorBase;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator
*/
class ConfiguratorTest extends Test
{
	public function setUp()
	{
		$this->configurator = new Configurator;
	}

	/**
	* @testdox $configurator->customFilters is an instance of FilterCollection
	*/
	public function testCustomFiltersInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\FilterCollection',
			$this->configurator->customFilters
		);
	}

	/**
	* @testdox $configurator->plugins is an instance of PluginCollection
	*/
	public function testPluginsInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\PluginCollection',
			$this->configurator->plugins
		);
	}

	/**
	* @testdox $configurator->rootRules is an instance of Ruleset
	*/
	public function testRootRulesInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\Ruleset',
			$this->configurator->rootRules
		);
	}

	/**
	* @testdox $configurator->tags is an instance of TagCollection
	*/
	public function testTagsInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\TagCollection',
			$this->configurator->tags
		);
	}

	/**
	* @testdox $configurator->urlConfig is an instance of UrlConfig
	*/
	public function testUrlConfigInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\UrlConfig',
			$this->configurator->urlConfig
		);
	}

	/**
	* @testdox asConfig() returns an array with a rootContext element
	*/
	public function testAsConfigRootContext()
	{
		$config = $this->configurator->asConfig();
		$this->assertInternalType('array', $config);
		$this->assertArrayHasKey('rootContext', $config);
	}

	/**
	* @testdox asConfig() returns an array with a registeredVars element that contains urlConfig
	*/
	public function testAsConfigRegisteredVars()
	{
		$config = $this->configurator->asConfig();
		$this->assertInternalType('array', $config);
		$this->assertArrayHasKey('registeredVars', $config);
		$this->assertArrayHasKey('urlConfig', $config['registeredVars']);
	}

	/**
	* @testdox asConfig() adds regexpLimit to the plugin's configuration if it's not specified
	*/
	public function testAsConfigAddRegexpLimit()
	{
		$this->configurator->plugins->add(
			'Dummy',
			new DummyPluginConfigurator($this->configurator)
		);
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			array(
				'plugins' => array(
					'Dummy' => array(
						'regexpLimit' => 1000
					)
				)
			),
			$config
		);
	}

	/**
	* @testdox asConfig() adds regexpLimitAction to the plugin's configuration if it's not specified
	*/
	public function testAsConfigAddRegexpLimitAction()
	{
		$this->configurator->plugins->add(
			'Dummy',
			new DummyPluginConfigurator($this->configurator)
		);
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			array(
				'plugins' => array(
					'Dummy' => array(
						'regexpLimitAction' => 'warn'
					)
				)
			),
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
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			array(
				'plugins' => array(
					'Dummy' => array(
						'quickMatch' => 'foo'
					)
				)
			),
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
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			array(
				'plugins' => array(
					'Dummy' => array(
						'quickMatch' => null
					)
				)
			),
			$config
		);
	}

	/**
	* @testdox allowedChildren and allowedDescendants bitfields are added to each tag
	*/
	public function testAsConfigTagBitfields()
	{
		$this->configurator->tags->add('A');
		$config = $this->configurator->asConfig();

		$this->assertArrayMatches(
			array(
				'tags' => array(
					'A' => array(
						'allowedChildren'    => "\1",
						'allowedDescendants' => "\1"
					)
				)
			),
			$config
		);
	}

	/**
	* @testdox getParser() returns an instance of s9e\TextFormatter\Parser
	*/
	public function testGetParser()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Parser',
			$this->configurator->getParser()
		);
	}

	/**
	* @testdox $configurator->BBCodes returns $configurator->plugins->load('BBCodes') if the plugin hasn't been loaded already
	*/
	public function testMagicGetLoad()
	{
		$mock = $this->getMock('stdClass', array('exists', 'load'));

		$mock->expects($this->once())
		     ->method('exists')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue(false));

		$mock->expects($this->once())
		     ->method('load')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue('foobar'));

		$this->configurator->plugins = $mock;

		$this->assertSame('foobar', $this->configurator->BBCodes);
	}

	/**
	* @testdox $configurator->BBCodes returns $configurator->plugins->get('BBCodes') if the plugin has already been loaded
	*/
	public function testMagicGetGet()
	{
		$mock = $this->getMock('stdClass', array('exists', 'get'));

		$mock->expects($this->once())
		     ->method('exists')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue(true));

		$mock->expects($this->once())
		     ->method('get')
		     ->with($this->equalTo('BBCodes'))
		     ->will($this->returnValue('foobar'));

		$this->configurator->plugins = $mock;

		$this->assertSame('foobar', $this->configurator->BBCodes);
	}

	/**
	* @testdox $configurator->foo throws an exception
	* @expectedException RuntimeException
	* @expectedExceptionMessage Undefined property
	*/
	public function testMagicGetInvalid()
	{
		$this->configurator->foo;
	}
}

class DummyPluginConfigurator extends ConfiguratorBase
{
	protected $config = array('foo' => 1);

	public function asConfig()
	{
		return $this->config;
	}

	public function setConfig(array $config)
	{
		$this->config = $config;
	}
}