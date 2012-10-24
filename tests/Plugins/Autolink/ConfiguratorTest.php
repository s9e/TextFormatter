<?php

namespace s9e\TextFormatter\Tests\Configurator\Items;

use s9e\TextFormatter\Tests\Test;
use s9e\TextFormatter\Plugins\Autolink\Configurator;

/**
* @covers s9e\TextFormatter\Plugins\Autolink\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Automatically creates an URL tag
	*/
	public function testCreatesTag()
	{
		$this->configurator->plugins->load('Autolink');
		$this->assertTrue($this->configurator->tags->exists('URL'));
	}
}