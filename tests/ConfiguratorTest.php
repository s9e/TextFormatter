<?php

namespace s9e\TextFormatter\Tests;

use s9e\TextFormatter\Configurator;
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
	* @testdox $configurator->rootRules is an instance of TagCollection
	*/
	public function testTagsInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\Collections\\TagCollection',
			$this->configurator->tags
		);
	}

	/**
	* @testdox $configurator->rootRules is an instance of UrlConfig
	*/
	public function testUrlConfigInstance()
	{
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Configurator\\UrlConfig',
			$this->configurator->urlConfig
		);
	}

	/**
	* @testdox asConfig() returns the parser's configuration as an array
	*/
	public function testAsConfig()
	{
		$config = $this->configurator->asConfig();
		$this->assertInternalType('array', $config);
		$this->assertArrayHasKey('rootContext', $config);
		$this->assertArrayHasKey('urlConfig', $config);
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
	* @testdox $configurator->CamelCase returns $configurator->plugins->get('CamelCase')
	*/
	public function testMagicGet()
	{
		$mock = $this->getMock('stdClass', array('get'));

		$mock->expects($this->once())
		     ->method('get')
		     ->with($this->equalTo('CamelCase'))
		     ->will($this->returnValue('foobar'));

		$this->configurator->plugins = $mock;

		$this->assertSame('foobar', $this->configurator->CamelCase);
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