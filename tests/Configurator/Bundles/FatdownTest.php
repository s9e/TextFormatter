<?php

namespace s9e\TextFormatter\Tests\Configurator\Bundles;

use s9e\TextFormatter\Configurator\Bundles\Fatdown;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Bundles\Fatdown
*/
class FatdownTest extends Test
{
	/**
	* @testdox Features
	*/
	public function testFeatures()
	{
		$configurator = Fatdown::getConfigurator();

		$this->assertTrue(isset($configurator->Autoemail));
		$this->assertTrue(isset($configurator->Autolink));
		$this->assertTrue(isset($configurator->Escaper));
		$this->assertTrue(isset($configurator->FancyPants));
		$this->assertTrue(isset($configurator->HTMLComments));
		$this->assertTrue(isset($configurator->HTMLElements));
		$this->assertTrue(isset($configurator->HTMLEntities));
		$this->assertTrue(isset($configurator->Litedown));
		$this->assertTrue(isset($configurator->MediaEmbed));
	}
}