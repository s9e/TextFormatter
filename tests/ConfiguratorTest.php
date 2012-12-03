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
	* @testdox asConfig() returns the parser's configuration
	*/
	public function testAsConfig()
	{
		$this->configurator->asConfig();
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
}