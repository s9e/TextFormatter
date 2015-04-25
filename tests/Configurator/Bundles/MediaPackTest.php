<?php

namespace s9e\TextFormatter\Tests\Configurator\Bundles;

use s9e\TextFormatter\Configurator;
use s9e\TextFormatter\Configurator\Bundles\MediaPack;
use s9e\TextFormatter\Tests\Test;

/**
* @covers s9e\TextFormatter\Configurator\Bundles\MediaPack
*/
class MediaPackTest extends Test
{
	/**
	* @testdox Features
	*/
	public function testFeatures()
	{
		$configurator = MediaPack::getConfigurator();

		$this->assertTrue(isset($configurator->MediaEmbed));
		$this->assertTrue(isset($configurator->tags['YOUTUBE']));
	}

	/**
	* @testdox Does not create BBCodes by default
	*/
	public function testNoBBCodes()
	{
		$configurator = MediaPack::getConfigurator();

		$this->assertFalse(isset($configurator->BBCodes));
	}

	/**
	* @testdox Creates a MEDIA BBCode if the BBCodes plugin is loaded
	*/
	public function testBBCodes()
	{
		$configurator = new Configurator;
		$configurator->BBCodes;

		$bundleConfigurator = new MediaPack;
		$bundleConfigurator->configure($configurator);

		$this->assertTrue(isset($configurator->BBCodes['MEDIA']));
	}
}