<?php

namespace s9e\TextFormatter\Tests\Configurator\Collections;

use Exception;
use s9e\TextFormatter\Configurator\Collections\PluginCollection;
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
			's9e\\TextFormatter\\Plugins\\RawHTML\\Config',
			$this->configurator->plugins['RawHTML']
		);
	}
}