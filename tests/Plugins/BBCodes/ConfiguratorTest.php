<?php

namespace s9e\TextFormatter\Tests\Plugins\BBCodes;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\Autolink\Configurator;

/**
* @covers s9e\TextFormatter\Plugins\BBCodes\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically loads its default BBCode repository
	*/
	public function testDefaultRepository()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$this->assertTrue(isset($plugin->repositories['default']));
	}

	/**
	* @testdox addFromRepository('B') adds the BBCode B and its tag from the default repository
	*/
	public function testAddFromRepository()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$plugin->addFromRepository('B');

		$this->assertTrue(isset($plugin['B']));
		$this->assertTrue(isset($this->configurator->tags['B']));
	}

	/**
	* @testdox toConfig() returns FALSE if no BBCodes were created
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$this->assertFalse($plugin->toConfig());
	}
}