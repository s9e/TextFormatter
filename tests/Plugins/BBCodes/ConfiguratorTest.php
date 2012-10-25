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
	* @testdox toConfig() returns FALSE if no BBCodes were created
	*/
	public function testFalseConfig()
	{
		$plugin = $this->configurator->plugins->load('BBCodes');
		$this->assertFalse($plugin->toConfig());
	}
}