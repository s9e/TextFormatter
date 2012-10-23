<?php

namespace s9e\TextFormatter\Tests\Generator\Collections;

use Exception;
use s9e\TextFormatter\Generator\Collections\PluginCollection;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Generator\Collections\PluginCollection
*/
class PluginCollectionTest extends Test
{
	/**
	* @testdox load() can load a stock plugin
	*/
	public function testLoad()
	{
		$this->generator->plugins->load('RawHTML');
		$this->assertInstanceOf(
			's9e\\TextFormatter\\Plugins\\RawHTML\\Config',
			$this->generator->plugins['RawHTML']
		);
	}
}