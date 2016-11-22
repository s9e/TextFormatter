<?php

namespace s9e\TextFormatter\Tests\Plugins\PipeTables;

use s9e\TextFormatter\Configurator\Items\AttributeFilters\UrlFilter;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Plugins\PipeTables\Configurator
*/
class ConfiguratorTest extends Test
{
	/**
	* @testdox Creates TABLE, TBODY, TD, TH, THEAD and TR tags
	*/
	public function testCreatesTags()
	{
		$this->configurator->PipeTables;
		$this->assertTrue(isset($this->configurator->tags['TABLE']));
		$this->assertTrue(isset($this->configurator->tags['TBODY']));
		$this->assertTrue(isset($this->configurator->tags['TD']));
		$this->assertTrue(isset($this->configurator->tags['TH']));
		$this->assertTrue(isset($this->configurator->tags['THEAD']));
		$this->assertTrue(isset($this->configurator->tags['TR']));
	}

	/**
	* @testdox Does not attempt to create a tag if it already exists
	*/
	public function testDoesNotCreateTag()
	{
	}
}